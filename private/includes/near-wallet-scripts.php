<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 自動攔截死節點，讀取 config.php 的全域 RPC 池，切換至最快 RPC，永不死機！
 */
?>
<script src="https://cdn.jsdelivr.net/npm/near-api-js@0.44.2/dist/near-api-js.min.js"></script>

<script>
    window.nearHubWalletWrapper = null;
    
    // 🌟 從 config.php 動態注入全域 RPC 備援池
    window.rpcNodesPool = <?= json_encode(defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : ["https://free.rpc.fastnear.com", "https://rpc.mainnet.near.org"]) ?>;
    window.activeNearRpcUrl = window.rpcNodesPool[0]; 

    // 🌟 核心：自動測試並挑選最快、無 CORS 阻擋的 RPC 節點
    async function getHealthyRpc() {
        for (const url of window.rpcNodesPool) {
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
        return window.rpcNodesPool[0]; // 如果全部失敗，夾硬用第一個博一博
    }

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        try {
            const { connect, keyStores, WalletConnection, transactions, utils } = window.nearApi;
            const BN = utils.BN; 

            // 🚀 執行 RPC 測速
            window.activeNearRpcUrl = await getHealthyRpc();

            const config = {
                networkId: "<?= defined('NEAR_NETWORK_ID') ? NEAR_NETWORK_ID : 'mainnet' ?>",
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
            alert("<?= addslashes(__('RPC Connection Failed')) ?>");
        }
    };
</script>