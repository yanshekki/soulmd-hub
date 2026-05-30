<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 🚨 Patched: Routed functionCall through FullAccess Key Extractor to guarantee signing!
 */
?>
<script src="https://cdn.jsdelivr.net/npm/near-api-js@2.0.4/dist/near-api-js.min.js"></script>

<script>
    window.nearHubWalletWrapper = null;
    window.rpcNodesPool = <?= json_encode(defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : ["https://free.rpc.fastnear.com", "https://rpc.mainnet.near.org"]) ?>;
    window.activeNearRpcUrl = window.rpcNodesPool[0]; 

    async function getHealthyRpc() {
        for (const url of window.rpcNodesPool) {
            try {
                const controller = new AbortController();
                const id = setTimeout(() => controller.abort(), 2500);
                const res = await fetch(url, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ jsonrpc: "2.0", id: "ping", method: "status", params: [] }),
                    signal: controller.signal
                });
                clearTimeout(id);
                if (res.ok) return url;
            } catch (e) {
                console.warn(`⚠️ RPC ${url} blocked or dead. Switching to next...`);
            }
        }
        return window.rpcNodesPool[0]; 
    }

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        try {
            const { connect, keyStores, WalletConnection, transactions, utils } = window.nearApi;
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
                requestSignIn: ({ contractId }) => { wallet.requestSignIn({ contractId: contractId }); },
                signOut: () => { wallet.signOut(); },
                account: () => {
                    return {
                        // 🚨 終極修復：將單一 call 劫持並打包進批量交易，強制使用 FullAccess Key！
                        functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                            return window.nearHubWalletWrapper.requestSignTransactions({
                                transactions: [{
                                    receiverId: contractId,
                                    actions: [{
                                        methodName: methodName,
                                        args: args,
                                        gas: gas || "30000000000000",
                                        deposit: attachedDeposit || "0"
                                    }]
                                }],
                                callbackUrl: walletCallbackUrl
                            });
                        }
                    };
                },
                requestSignTransactions: async ({ transactions: txs, callbackUrl }) => {
                    try {
                        const accountId = wallet.getAccountId();
                        const block = await near.connection.provider.block({ finality: 'final' });
                        const blockHash = utils.serialize.base_decode(block.header.hash);
                        
                        // 🚨 強制向區塊鏈索取 FullAccess Key
                        const accessKeys = await near.connection.provider.query({ request_type: 'view_access_key_list', account_id: accountId, finality: 'final' });
                        const fullAccessKey = accessKeys.keys.find(k => k.access_key.permission === 'FullAccess');
                        
                        if (!fullAccessKey) {
                            alert("No FullAccess key found for your wallet. Please re-login.");
                            wallet.signOut(); window.location.reload(); return;
                        }

                        const realPublicKey = utils.PublicKey.from(fullAccessKey.public_key);
                        const encoder = new TextEncoder();

                        const realTxs = txs.map((tx, index) => {
                            const parsedActions = tx.actions.map(action => {
                                const argsData = (!action.args || Object.keys(action.args).length === 0) ? new Uint8Array(0) : encoder.encode(JSON.stringify(action.args));
                                const actionGas = typeof utils.BN !== 'undefined' ? new utils.BN(action.gas.toString()) : BigInt(action.gas.toString());
                                const actionDep = typeof utils.BN !== 'undefined' ? new utils.BN(action.deposit.toString()) : BigInt(action.deposit.toString());

                                return transactions.functionCall(action.methodName, argsData, actionGas, actionDep);
                            });

                            return transactions.createTransaction(accountId, realPublicKey, tx.receiverId, index + 1, parsedActions, blockHash);
                        });

                        return wallet.requestSignTransactions({ transactions: realTxs, callbackUrl: callbackUrl });
                    } catch (err) {
                        console.error("requestSignTransactions error:", err); throw err; 
                    }
                }
            };
            return window.nearHubWalletWrapper;
        } catch (err) {
            console.error("NEAR Wallet Init Error:", err);
            alert("<?= addslashes(__('RPC Connection Failed')) ?>");
        }
    };
</script>