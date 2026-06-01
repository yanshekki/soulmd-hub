<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 🚨 Patched: Restored Silent Sign (0 Deposit) & Smart FullAccess Router
 */

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
                    const originalAccount = wallet.account();
                    return {
                        functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                            const depositStr = (attachedDeposit || "0").toString();
                            
                            // 🚨 終極 UX 修復：0 Deposit 操作 (如 Burn, Update Hash) 使用原生靜默簽署 (Silent Sign)
                            // 這樣就不會再每次都跳轉去 MyNearWallet 要求授權！
                            if (depositStr === "0") {
                                const gasVal = typeof utils.BN !== 'undefined' ? new utils.BN((gas || "30000000000000").toString()) : BigInt((gas || "30000000000000").toString());
                                const depVal = typeof utils.BN !== 'undefined' ? new utils.BN("0") : BigInt(0);
                                
                                return originalAccount.functionCall({
                                    contractId: contractId,
                                    methodName: methodName,
                                    args: args,
                                    gas: gasVal,
                                    attachedDeposit: depVal,
                                    walletCallbackUrl: walletCallbackUrl
                                });
                            } else {
                                // 💸 大於 0 Deposit (如買賣、Mint) 則強制使用 FullAccess 並跳轉錢包
                                return window.nearHubWalletWrapper.requestSignTransactions({
                                    transactions: [{
                                        receiverId: contractId,
                                        actions: [{
                                            methodName: methodName,
                                            args: args,
                                            gas: gas || "30000000000000",
                                            deposit: depositStr
                                        }]
                                    }],
                                    callbackUrl: walletCallbackUrl
                                });
                            }
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

    window.connectOrBindWallet = async function() {
        const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
        const dbWallet = "<?= $sync_phpUserWallet ?>";

        if (!isPhpLoggedIn) {
            window.location.href = '<?= url("/login") ?>';
            return;
        }

        if (!dbWallet) {
            window.location.href = '<?= url("/my-setting") ?>?tab=web3';
            return;
        }

        const wallet = await window.initNearWallet();
        if (!wallet.isSignedIn()) {
            wallet.requestSignIn({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" });
        } else {
            window.location.reload();
        }
    };

    window.addEventListener('DOMContentLoaded', async () => {
        const isAuthPage = window.location.pathname.includes('/login') || window.location.pathname.includes('/register');
        
        try {
            const wallet = await window.initNearWallet();
            const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
            const phpWalletAddress = "<?= $sync_phpUserWallet ?>";

            if (wallet.isSignedIn()) {
                const currentWeb3Wallet = wallet.getAccountId();

                if (!isPhpLoggedIn) {
                    const res = await fetch('/api/wallet-login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ account_id: currentWeb3Wallet })
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        if (!isAuthPage) {
                            window.location.reload();
                        } else {
                            window.location.href = '<?= url("/my-souls") ?>';
                        }
                    } else {
                        console.warn("Web3 Wallet not bound to Web2 account. Forcing sync logout.");
                        wallet.signOut();
                        if (!isAuthPage) {
                            alert("<?= addslashes(__('Wallet not bound alert')) ?>");
                            window.location.href = '<?= url("/login") ?>';
                        }
                    }
                } else {
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