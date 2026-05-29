<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS (MyNearWallet ONLY)
 * 100% 防彈版：自帶 Buffer 與 BN.js，徹底消滅「按鈕無反應」嘅靜默崩潰！
 */
?>
<script src="https://cdn.jsdelivr.net/npm/buffer@6.0.3/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bn.js@5.2.1/lib/bn.min.js"></script>
<script>
    window.global = window;
    window.Buffer = window.Buffer || buffer.Buffer;
    window.BN = window.BN || bn;
</script>

<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>

<script>
    window.nearHubWalletWrapper = null;

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        try {
            const { connect, keyStores, WalletConnection, transactions, utils } = window.nearApi;
            
            // 🚀 最原始、最穩定的 MyNearWallet 直連參數
            const config = {
                networkId: "mainnet",
                keyStore: new keyStores.BrowserLocalStorageKeyStore(),
                nodeUrl: "https://rpc.mainnet.near.org",
                walletUrl: "https://app.mynearwallet.com",
            };

            const near = await connect(config);
            const wallet = new WalletConnection(near, 'soulmd_hub');

            // 🛡️ 完美封裝，確保全站向下相容
            window.nearHubWalletWrapper = {
                isSignedIn: () => wallet.isSignedIn(),
                getAccountId: () => wallet.getAccountId(),
                requestSignIn: ({ contractId }) => {
                    // 原生跳轉模式，保證 100% 觸發網頁跳轉
                    wallet.requestSignIn({ contractId: contractId });
                },
                signOut: () => {
                    wallet.signOut();
                },
                account: () => {
                    return {
                        functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                            return wallet.account().functionCall({
                                contractId,
                                methodName,
                                args,
                                // 強制使用 BN.js 轉換數字，防止 BigInt 報錯
                                gas: new window.BN((gas || "30000000000000").toString()),
                                attachedDeposit: new window.BN((attachedDeposit || "0").toString()),
                                walletCallbackUrl
                            });
                        }
                    };
                },
                requestSignTransactions: async ({ transactions: txs, callbackUrl }) => {
                    const accountId = wallet.getAccountId();
                    const block = await near.connection.provider.block({ finality: 'final' });
                    const blockHash = utils.serialize.base_decode(block.header.hash);
                    const dummyPublicKey = utils.PublicKey.from('ed25519:11111111111111111111111111111111');

                    const realTxs = txs.map((tx, index) => {
                        const parsedActions = tx.actions.map(action => {
                            return transactions.functionCall(
                                action.methodName,
                                // 強制轉為 Uint8Array 防止序列化崩潰
                                new Uint8Array(window.Buffer.from(JSON.stringify(action.args))),
                                new window.BN(action.gas.toString()),
                                new window.BN(action.deposit.toString())
                            );
                        });

                        return transactions.createTransaction(
                            accountId,
                            dummyPublicKey,
                            tx.receiverId,
                            index + 1, 
                            parsedActions,
                            blockHash
                        );
                    });

                    return wallet.requestSignTransactions({
                        transactions: realTxs,
                        callbackUrl: callbackUrl
                    });
                }
            };

            return window.nearHubWalletWrapper;
            
        } catch (err) {
            console.error("NEAR Wallet Init Error:", err);
            alert("Web3 核心組件載入失敗，請強制重新整理 (Ctrl+F5) 再試！");
        }
    };
</script>