<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS (MyNearWallet ONLY)
 * 100% FIXED: 換上 Browser 專用 Buffer 墊片，徹底解決靜默死機！
 */
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/buffer/6.0.3/buffer.min.js"></script>
<script>
    // 綁定全域 Buffer，等 near-api-js 順利搵到佢，唔會再 Crash！
    window.Buffer = window.Buffer || buffer.Buffer;
</script>

<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>

<script>
    window.nearHubWalletWrapper = null;

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        try {
            // 從成功載入的 nearApi 中提取必要工具，包含官方內建的 BN 處理器
            const { connect, keyStores, WalletConnection, transactions, utils } = window.nearApi;
            const BN = utils.BN; 

            // 🚀 最原始、最穩定的 MyNearWallet 直連參數
            const config = {
                networkId: "mainnet",
                keyStore: new keyStores.BrowserLocalStorageKeyStore(),
                nodeUrl: "https://rpc.mainnet.near.org",
                walletUrl: "https://app.mynearwallet.com",
            };

            const near = await connect(config);
            const wallet = new WalletConnection(near, 'soulmd_hub');

            // 🛡️ 完美封裝，確保全站 Marketplace / Login 向下相容
            window.nearHubWalletWrapper = {
                isSignedIn: () => wallet.isSignedIn(),
                getAccountId: () => wallet.getAccountId(),
                requestSignIn: ({ contractId }) => {
                    // 原生跳轉模式，保證 100% 觸發網頁跳轉，再無彈窗煩惱！
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
                                gas: new BN((gas || "30000000000000").toString()),
                                attachedDeposit: new BN((attachedDeposit || "0").toString()),
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
                                new Uint8Array(window.Buffer.from(JSON.stringify(action.args || {}))),
                                new BN(action.gas.toString()),
                                new BN(action.deposit.toString())
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
            alert("錢包模組載入失敗，請重新整理頁面 (Ctrl+F5)！");
        }
    };
</script>