<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 🚨 Patched: Global Web2 <-> Web3 Session Synchronization Engine
 */

// 🌟 獲取當前 PHP Web2 狀態，供前端 JS 進行狀態同步比對
$sync_isPhpLoggedIn = isset($_SESSION['user_id']) ? 'true' : 'false';
$sync_phpUserWallet = '';
if (isset($_SESSION['user_id'])) {
    try {
        global $pdo;
        if (!isset($pdo)) {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
        }
        $sync_stmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
        $sync_stmt->execute([$_SESSION['user_id']]);
        $sync_phpUserWallet = $sync_stmt->fetchColumn() ?: '';
    } catch (Exception $e) {}
}
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

    // 🌟 全域 Web2 & Web3 狀態同步引擎 (Global Session Synchronization Engine)
    window.addEventListener('DOMContentLoaded', async () => {
        const isAuthPage = window.location.pathname.includes('/login') || window.location.pathname.includes('/register');
        
        try {
            // 初始化並檢查 Web3 狀態
            const wallet = await window.initNearWallet();
            const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
            const phpWalletAddress = "<?= $sync_phpUserWallet ?>";

            if (wallet.isSignedIn()) {
                const currentWeb3Wallet = wallet.getAccountId();

                if (!isPhpLoggedIn) {
                    // 情況 A：Web3 連接了，但 Web2 沒有登入 -> 嘗試無縫自動同步登入 Web2
                    const res = await fetch('/api/wallet-login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ account_id: currentWeb3Wallet })
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        // 同步登入成功！刷新頁面應用 Web2 Session (若是從 login 頁面進入則直接跳轉)
                        if (!isAuthPage) {
                            window.location.reload();
                        } else {
                            window.location.href = '<?= url("/my-souls") ?>';
                        }
                    } else {
                        // 錢包未綁定任何 Web2 帳號！強制登出 Web3 以保持兩端無狀態 (Stateless)
                        console.warn("Web3 Wallet not bound to Web2 account. Forcing sync logout.");
                        wallet.signOut();
                        if (!isAuthPage) {
                            alert("<?= addslashes(__('Wallet not bound alert')) ?>");
                            window.location.href = '<?= url("/login") ?>';
                        }
                    }
                } else {
                    // 情況 B：兩邊都登入了，嚴格檢查是否為同一個錢包 (防止用戶手動切換帳號導致越權)
                    if (phpWalletAddress && currentWeb3Wallet !== phpWalletAddress) {
                        console.warn("Web2 and Web3 Wallet mismatch. Forcing Web3 sync.");
                        wallet.signOut();
                        alert("<?= addslashes(__('Wallet mismatch alert')) ?>");
                        window.location.reload();
                    }
                }
            }
        } catch (e) {
            console.error("Auto-sync engine error:", e);
        }
    });
</script>