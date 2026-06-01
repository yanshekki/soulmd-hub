<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 PURE VANILLA JS + DYNAMIC RPC FAILOVER (V5 Centralized Config Edition)
 * 🚨 Patched: Added Cryptographic Signature Generation for secure Wallet Login/Binding
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

    // 🚀 全新核心服務：全域通用 RPC 查詢引擎 (View Call)
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

    // 🚀 全新核心服務：生成防偽密碼學簽章 (用於 Login 與 Bind)
    window.generateNearAuthPayload = async function(accountId) {
        const { keyStores } = window.nearApi;
        const keyStore = new keyStores.BrowserLocalStorageKeyStore();
        const networkId = "<?= defined('NEAR_NETWORK_ID') ? NEAR_NETWORK_ID : 'mainnet' ?>";
        
        // 從 LocalStorage 獲取已經被授權的 Session Private Key
        const keyPair = await keyStore.getKey(networkId, accountId);
        
        if (!keyPair) {
            throw new Error("No local key found for this account. Please reconnect wallet.");
        }

        // 建立帶有時間戳的防重放攻擊 Nonce
        const timestamp = Date.now();
        const message = "soulmd_auth:" + timestamp;
        const msgBytes = new TextEncoder().encode(message);
        
        // 進行 Ed25519 簽名
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
                account: () => {
                    const originalAccount = wallet.account();
                    return {
                        functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                            const depositStr = (attachedDeposit || "0").toString();
                            
                            // 0 Deposit 操作使用原生靜默簽署 (Silent Sign)
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
                                // 大於 0 Deposit 強制使用 FullAccess 並跳轉錢包
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
            alert("RPC Connection Failed");
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

    // 🚨 修正：自動背景登入驗證時，加入簽章 Payload
    window.addEventListener('DOMContentLoaded', async () => {
        const isAuthPage = window.location.pathname.includes('/login') || window.location.pathname.includes('/register');
        
        try {
            const wallet = await window.initNearWallet();
            const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
            const phpWalletAddress = "<?= $sync_phpUserWallet ?>";

            if (wallet.isSignedIn()) {
                const currentWeb3Wallet = wallet.getAccountId();

                if (!isPhpLoggedIn) {
                    // 自動生成安全 Payload
                    const authPayload = await window.generateNearAuthPayload(currentWeb3Wallet);

                    const res = await fetch('/api/wallet-login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(authPayload)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        if (!isAuthPage) {
                            window.location.reload();
                        } else {
                            window.location.href = '<?= url("/my-souls") ?>';
                        }
                    } else {
                        console.warn("Web3 Wallet auth failed. Forcing sync logout.");
                        wallet.signOut();
                        if (!isAuthPage) {
                            window.location.href = '<?= url("/login") ?>';
                        }
                    }
                } else {
                    if (phpWalletAddress && currentWeb3Wallet !== phpWalletAddress) {
                        console.warn("Web2 and Web3 Wallet mismatch. Forcing Web3 sync.");
                        wallet.signOut();
                        alert("Wallet mismatch detected. For your security, the Web3 session has been disconnected.");
                        window.location.reload();
                    }
                }
            }
        } catch (e) {
            console.error("Auto-sync engine error:", e);
        }
    });
</script>