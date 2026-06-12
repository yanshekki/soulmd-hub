<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * 🚀 V16 INDESTRUCTIBLE: Dual-Action Format (Fixes "Unsupported NAJ action")
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

<link rel="stylesheet" href="<?= url('/assets/wallet-selector-style.css?v=1.0.0') ?>">
<script src="<?= url('/assets/wallet-selector-bundle.js?v=1.0.0') ?>"></script>

<script>
    window.nearHubWalletWrapper = null;
    window.walletSelectorInstance = null;
    window.walletModalInstance = null;
    window.isSignLocked = false; 

    window.rpcNodesPool = <?= json_encode(defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : ["https://free.rpc.fastnear.com", "https://rpc.mainnet.near.org"]) ?>;
    window.activeNearRpcUrl = window.rpcNodesPool[0];

    async function getHealthyRpc() {
        // Use NEAR_RPC_NODES and find the FASTEST RPC by measured ping time (for all NEAR RPC usage in wallet/upgrade flows)
        const results = await Promise.allSettled(
            window.rpcNodesPool.map(async (url) => {
                const start = performance.now();
                try {
                    const controller = new AbortController();
                    const id = setTimeout(() => controller.abort(), 3000);
                    const res = await fetch(url, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ jsonrpc: "2.0", id: "ping", method: "status", params: [] }),
                        signal: controller.signal
                    });
                    clearTimeout(id);
                    const time = performance.now() - start;
                    if (res.ok) {
                        const data = await res.json().catch(() => ({}));
                        if (data && data.result) return { url, time };
                    }
                } catch (e) {}
                return null;
            })
        );
        const valid = results
            .map(r => (r.status === 'fulfilled' ? r.value : null))
            .filter(Boolean);
        if (valid.length === 0) return window.rpcNodesPool[0];
        valid.sort((a, b) => a.time - b.time);
        return valid[0].url;
    }

    window.nearRpcQuery = async function(methodName, args = {}, finality = 'optimistic') {
        const contractId = "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>";
        const argsBase64 = btoa(unescape(encodeURIComponent(JSON.stringify(args))));

        const payload = {
            jsonrpc: "2.0", id: "soulmd_query", method: "query",
            params: { request_type: "call_function", finality: finality, account_id: contractId, method_name: methodName, args_base64: argsBase64 }
        };

        if (!window.activeNearRpcUrl) window.activeNearRpcUrl = await getHealthyRpc();
        const nodesToTry = [window.activeNearRpcUrl, ...window.rpcNodesPool.filter(url => url !== window.activeNearRpcUrl)];

        for (const url of nodesToTry) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 3500);
                const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload), signal: controller.signal });
                clearTimeout(timeoutId);

                if (res.ok) {
                    const data = await res.json();
                    // ✅ B 修復：前端 RPC 回應基本驗證
                    if (!data || data.jsonrpc !== "2.0" || data.id !== "soulmd_query") {
                        if (data && data.error) return { success: false, error: data.error, status: 'error' };
                        return { success: false, error: 'Invalid RPC response structure', status: 'error' };
                    }
                    if (data.error) return { success: false, error: data.error, status: 'error' };
                    if (data.result && data.result.result) {
                        const resString = new TextDecoder().decode(new Uint8Array(data.result.result));
                        if (resString.trim() === 'null') return { success: true, data: null, status: 'not_found' };
                        return { success: true, data: JSON.parse(resString), status: 'success' };
                    }
                }
            } catch (e) {
                if (url === window.activeNearRpcUrl) window.activeNearRpcUrl = null;
            }
        }
        return { success: false, error: 'All RPC nodes failed', status: 'timeout' };
    };

    window.nukeWalletState = async function() {
        try {
            if (window.walletSelectorInstance) {
                const wallet = await window.walletSelectorInstance.wallet().catch(()=>null);
                if (wallet) await wallet.signOut();
            }
        } catch(e) {}
        
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && (key.startsWith('near-wallet-selector') || key.startsWith('near-api-js:keystore:'))) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(k => localStorage.removeItem(k));
        window.isSignLocked = false; 
    };

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        const networkId = "<?= defined('NEAR_NETWORK_ID') ? NEAR_NETWORK_ID : 'mainnet' ?>";
        const contractId = "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>";

        try {
            if (typeof window.initWalletSelectorUI !== 'function') {
                throw new Error('initWalletSelectorUI not available (bundle not loaded or failed to expose)');
            }
            const { selector, modal } = await window.initWalletSelectorUI(networkId, contractId);
            window.walletSelectorInstance = selector;
            window.walletModalInstance = modal;

            window.walletSelectorInstance.on("signedIn", async ({ accounts }) => {
                if (window.isSignLocked) return;
                window.isSignLocked = true;

                const currentUrl = window.location.pathname;
                const accountId = accounts[0].accountId;
                
                if (currentUrl.includes('/login') && typeof window.verifyNearWallet === 'function') {
                    await window.verifyNearWallet(accountId);
                } 
                else if (currentUrl.includes('/my-setting') && typeof window.executeWalletBind === 'function') {
                    await window.executeWalletBind(accountId);
                } 
                else {
                    window.location.reload();
                }
            });

            window.walletSelectorInstance.on("signedOut", () => {
                window.isSignLocked = false;
                const isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
                if (isPhpLoggedIn) window.location.reload();
            });

            window.nearHubWalletWrapper = {
                isSignedIn: () => selector.isSignedIn(),
                getAccountId: () => {
                    const state = selector.store.getState();
                    return state.accounts.length > 0 ? state.accounts[0].accountId : null;
                },
                requestSignIn: () => { 
                    window.isSignLocked = false; 
                    modal.show();
                },
                signOut: async () => {
                    const wallet = await selector.wallet();
                    await wallet.signOut();
                },
                
                // 🚀 核心修復 1：單筆交易強制使用雙棲格式
                account: () => ({
                    functionCall: async ({ contractId: callContractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                        const wallet = await selector.wallet();
                        let mGas = (gas || "30000000000000").toString();
                        let mDep = (attachedDeposit || "0").toString();
                        
                        // 雙棲 Action (同時滿足新版與舊版 NAJ 格式)
                        const dualAction = {
                            type: "FunctionCall",
                            params: { methodName: methodName, args: args || {}, gas: mGas, deposit: mDep },
                            functionCall: { methodName: methodName, args: args || {}, gas: mGas, deposit: mDep }
                        };

                        return wallet.signAndSendTransaction({
                            receiverId: callContractId || contractId,
                            actions: [dualAction],
                            callbackUrl: walletCallbackUrl
                        });
                    }
                }),

                // 🚀 核心修復 2：批量交易強制使用雙棲格式
                requestSignTransactions: async ({ transactions, callbackUrl }) => {
                    const wallet = await selector.wallet();
                    const formattedTxs = transactions.map(tx => ({
                        receiverId: tx.receiverId,
                        actions: tx.actions.map(a => {
                            let mName = a.methodName || (a.params ? a.params.methodName : null) || (a.functionCall ? a.functionCall.methodName : null);
                            let mArgs = a.args || (a.params ? a.params.args : null) || (a.functionCall ? a.functionCall.args : null) || {};
                            let mGas = (a.gas || (a.params ? a.params.gas : null) || (a.functionCall ? a.functionCall.gas : null) || "30000000000000").toString();
                            let mDep = (a.deposit || a.attachedDeposit || (a.params ? a.params.deposit : null) || (a.functionCall ? a.functionCall.deposit : null) || "0").toString();
                            
                            // 雙棲 Action
                            return {
                                type: "FunctionCall",
                                params: { methodName: mName, args: mArgs, gas: mGas, deposit: mDep },
                                functionCall: { methodName: mName, args: mArgs, gas: mGas, deposit: mDep }
                            };
                        })
                    }));

                    return wallet.signAndSendTransactions({
                        transactions: formattedTxs,
                        callbackUrl: callbackUrl
                    });
                }
            };

            return window.nearHubWalletWrapper;
        } catch (err) {
            console.error("Wallet Selector Init Error:", err);
            // Always return a safe stub so callers never crash on .isSignedIn() etc.
            const stub = {
                isSignedIn: () => false,
                getAccountId: () => null,
                requestSignIn: () => {
                    if (window.walletModalInstance && typeof window.walletModalInstance.show === 'function') {
                        window.walletModalInstance.show();
                    } else if (window.location) {
                        window.location.href = '/my-setting#web3';
                    }
                },
                signOut: async () => {},
                account: () => ({
                    functionCall: async () => { throw new Error('Wallet not initialized. Please refresh or visit /my-setting to bind.'); }
                }),
                requestSignTransactions: async () => { throw new Error('Wallet not initialized. Please refresh or visit /my-setting to bind.'); }
            };
            window.nearHubWalletWrapper = stub;
            return stub;
        }
    };

    // 🚀 MISSING GLOBAL: connectOrBindWallet (called from marketplace, soul.php, my-souls, forms etc when !signedIn)
    // Centralized so no "not a function" errors on any page.
    window.connectOrBindWallet = async function() {
        try {
            const w = await window.initNearWallet();
            if (w && typeof w.requestSignIn === 'function') {
                w.requestSignIn();
                return;
            }
            if (window.walletModalInstance && typeof window.walletModalInstance.show === 'function') {
                window.walletModalInstance.show();
                return;
            }
        } catch (e) {
            console.warn('connectOrBindWallet init issue:', e);
        }
        // Final fallback: go bind in settings
        if (window.location) window.location.href = '/my-setting#web3';
    };

    // 🚀 ROBUST ERROR MESSAGE EXTRACTOR (fixes "e.message is undefined" in all transaction alerts)
    // Wallet selector, near-api, and user-rejected errors often have the real message in .message, .type, .error, or as a plain string/object.
    window.getErrorMessage = function(e) {
        if (!e) return 'Unknown error';
        if (typeof e === 'string') return e;
        if (e.message) return e.message;
        if (e.error) {
            if (typeof e.error === 'string') return e.error;
            if (e.error.message) return e.error.message;
        }
        if (e.type) return e.type + (e.message ? ' - ' + e.message : '');
        if (e.reason) return e.reason;
        if (e.details) return e.details;
        if (e.name && e.name !== 'Error') return e.name + (e.message ? ': ' + e.message : '');
        try {
            const str = JSON.stringify(e);
            return str.length > 300 ? str.substring(0, 300) + '...' : str;
        } catch (_) {
            return String(e);
        }
    };

    window.generateNearAuthPayload = async function(accountId) {
        const wallet = await window.walletSelectorInstance.wallet();
        const timestamp = Date.now();
        const message = "soulmd_auth:" + timestamp;

        const nonceArray = new Uint8Array(32);
        crypto.getRandomValues(nonceArray);
        const recipient = window.location.hostname;
        
        const nonceBuffer = window.Buffer.from(nonceArray);

        try {
            const result = await wallet.signMessage({
                message: message,
                nonce: nonceBuffer,
                recipient: recipient
            });

            if (!result) throw new Error("No payload returned from wallet.");

            return {
                account_id: result.accountId,
                public_key: result.publicKey, 
                signature: result.signature,  
                message: message,
                nonce: Array.from(nonceArray),
                recipient: recipient,
                is_nep0413: true
            };

        } catch (e) {
            console.error("Sign message failed:", e);
            window.isSignLocked = false; 
            throw new Error("Message signing was cancelled or failed.");
        }
    };
</script>