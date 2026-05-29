<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER
 * 自動攔截死節點，切換至最快 RPC，永不死機！
 */
?>
<script src="https://cdn.jsdelivr.net/npm/near-api-js@0.44.2/dist/near-api-js.min.js"></script>

<script>
    window.nearHubWalletWrapper = null;
    window.activeNearRpcUrl = "https://free.rpc.fastnear.com"; // 預設使用最快節點

    // 🌟 核心：自動測試並挑選最快、無 CORS 阻擋的 RPC 節點
    async function getHealthyRpc() {
        const rpcNodes = [
            "https://free.rpc.fastnear.com",   // FastNEAR (極速、無 CORS 限制)
            "https://near.lava.build",         // Lava Network (去中心化高可用)
            "https://rpc.mainnet.pagoda.co",   // Pagoda 官方企業節點
            "https://rpc.mainnet.near.org"     // NEAR 官方預設 (最後備用，易被 Block)
        ];

        for (const url of rpcNodes) {
            try {
                // 設定 2.5 秒超時，唔通即刻飛，唔會卡死個網頁
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), 2500);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ jsonrpc: "2.0", id: "ping", method: "status", params: [] }),
                    signal: controller.signal
                });
                clearTimeout(id);

                if (res.ok) {
                    console.log("✅ Web3 RPC Connected:", url);
                    return url;
                }
            } catch (e) {
                console.warn(`⚠️ RPC ${url} blocked or dead. Switching to next...`);
            }
        }
        return rpcNodes[0]; // 如果全部失敗，夾硬用第一個博一博
    }

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        try {
            const { connect, keyStores, WalletConnection, transactions, utils } = window.nearApi;
            const BN = utils.BN; 

            // 🚀 執行 RPC 測速
            window.activeNearRpcUrl = await getHealthyRpc();

            const config = {
                networkId: "mainnet",
                keyStore: new keyStores.BrowserLocalStorageKeyStore(),
                nodeUrl: window.activeNearRpcUrl,
                walletUrl: "https://app.mynearwallet.com",
            };

            const near = await connect(config);
            const wallet = new WalletConnection(near, 'soulmd_hub');

            window.nearHubWalletWrapper = {
                isSignedIn: () => wallet.isSignedIn(),
                getAccountId: () => wallet.getAccountId(),
                requestSignIn: ({ contractId }) => {
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
                    
                    const encoder = new TextEncoder();

                    const realTxs = txs.map((tx, index) => {
                        const parsedActions = tx.actions.map(action => {
                            return transactions.functionCall(
                                action.methodName,
                                encoder.encode(JSON.stringify(action.args || {})),
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
            alert("RPC 連線失敗，請檢查網絡或重新整理 (Ctrl+F5)！");
        }
    };
</script>