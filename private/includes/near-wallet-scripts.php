<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 🚨 Patched: 100% i18n Dynamic Language Layer Overridden + Security Handshake Patched
 */

// 🌍 強制加載 header 與 api 語言字典，確保所有前端 Wallet 提示字眼能精準翻譯
loadTranslations('header');
loadTranslations('api');

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

    // 🌟 動態尋找最快健康節點
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

    // 🚀 全域通用 RPC 查詢引擎 (View Call)
    window.nearRpcQuery = async function(methodName, args = {}, finality = 'optimistic') {
        const contractId = "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>";
        const argsBase64 = btoa(unescape(encodeURIComponent(JSON.stringify(args))));
        
        const payload = {
            jsonrpc: "2.0",
            id: "soulmd_query",
            method: "query",
            params: {
                request_type: "call_function",
                finality: finality,
                account_id: contractId,
                method_name: methodName,
                args_base64: argsBase64
            }
        };

        if (!window.activeNearRpcUrl) {
            window.activeNearRpcUrl = await getHealthyRpc();
        }

        const nodesToTry = [window.activeNearRpcUrl, ...window.rpcNodesPool.filter(url => url !== window.activeNearRpcUrl)];

        for (const url of nodesToTry) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3500);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (res.ok) {
                    const data = await res.json();
                    if (data.error) {
                        return { success: false, error: data.error, status: 'error' };
                    }
                    if (data.result && data.result.result) {
                        const resString = new TextDecoder().decode(new Uint8Array(data.result.result));
                        if (resString.trim() === 'null') {
                            return { success: true, data: null, status: 'not_found' }; 
                        }
                        return { success: true, data: JSON.parse(resString), status: 'success' };
                    }
                }
            } catch (e) {
                if (url === window.activeNearRpcUrl) {
                    window.activeNearRpcUrl = null; 
                }
            }
        }
        
        return { success: false, error: 'All RPC nodes failed', status: 'timeout' };
    };

    // 🚀 生成防偽密碼學簽章 (用於 Login 與 Bind)
    window.generateNearAuthPayload = async function(accountId) {
        const { keyStores } = window.nearApi;
        const keyStore = new keyStores.BrowserLocalStorageKeyStore();
        const networkId = "<?= defined('NEAR_NETWORK_ID') ? NEAR_NETWORK_ID : 'mainnet' ?>";
        
        const keyPair = await keyStore.getKey(networkId, accountId);
        
        if (!keyPair) {
            // 🚨 i18n 注入：本地找不到 KeyPair 異常
            throw new Error("<?= addslashes(__('No local key found for this account. Please reconnect wallet.')) ?>");
        }

        const timestamp = Date.now();
        const message = "soulmd_auth:" + timestamp;
        const msgBytes = new TextEncoder().encode(message);
        
        const { signature } = keyPair.sign(msgBytes);
        const signatureB64 = btoa(String.fromCharCode(...signature));
        const publicKey = keyPair.getPublicKey().toString();

        return {
            account_id: accountId,
            public_key: publicKey,
            signature: signatureB64,
            message: message
        };
    };

    // 🚀 錢包初始化與包裝器
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
                account: () => wallet.account(),
                
                requestSignTransactions: async ({ transactions: txs, callbackUrl }) => {
                    try {
                        const accountId = wallet.getAccountId();
                        const block = await near.connection.provider.block({ finality: 'final' });
                        const blockHash = utils.serialize.base_decode(block.header.hash);
                        
                        const accessKeys = await near.connection.provider.query({ request_type: 'view_access_key_list', account_id: accountId, finality: 'final' });
                        const functionCallKey = accessKeys.keys[0]; 
                        
                        if (!functionCallKey) {
                            // 🚨 i18n 注入：找尋不到 AccessKey 異常
                            alert("<?= addslashes(__('No access key found. Please re-login.')) ?>");
                            wallet.signOut(); window.location.reload(); return;
                        }

                        const realPublicKey = utils.PublicKey.from(functionCallKey.public_key);
                        const encoder = new TextEncoder();

                        const realTxs = txs.map((tx, index) => {
                            const parsedActions = tx.actions.map(action => {
                                const argsData = (!action.args || Object.keys(action.args).length === 0) ? new Uint8Array(0) : encoder.encode(JSON.stringify(action.args));
                                const actionGas = typeof utils.BN !== 'undefined' ? new utils.BN(action.gas.toString()) : BigInt(action.gas.toString());
                                const actionDep = typeof utils.BN !== 'undefined' ? new utils.BN((action.deposit || "0").toString()) : BigInt((action.deposit || "0").toString());

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
            // 🚨 i18n 注入：RPC 連線死機
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

    // 🚨 自動背景雙向登入狀態校驗
    window.addEventListener('DOMContentLoaded', async () => {
        const isAuthPage = window.location.pathname.includes('/login') || window.location.pathname.includes('/register');
        
        try {
            const wallet = await window.initNearWallet();
            const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
            const phpWalletAddress = "<?= $sync_phpUserWallet ?>";

            if (wallet.isSignedIn()) {
                const currentWeb3Wallet = wallet.getAccountId();

                if (!isPhpLoggedIn) {
                    const authPayload = await window.generateNearAuthPayload(currentWeb3Wallet);

                    const res = await fetch('/api/wallet-login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(authPayload)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        if (!isAuthPage) { window.location.reload(); } 
                        else { window.location.href = '<?= url("/my-souls") ?>'; }
                    } else {
                        console.warn("Web3 Wallet auth failed. Forcing sync logout.");
                        wallet.signOut();
                        if (!isAuthPage) { window.location.href = '<?= url("/login") ?>'; }
                    }
                } else {
                    if (phpWalletAddress && currentWeb3Wallet !== phpWalletAddress) {
                        console.warn("Web2 and Web3 Wallet mismatch. Forcing Web3 sync.");
                        wallet.signOut();
                        // 🚨 i18n 注入：完美的雙胞胎錢包狀態衝突警報（已調配你 header.php 定義好的 'Wallet mismatch alert'）
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