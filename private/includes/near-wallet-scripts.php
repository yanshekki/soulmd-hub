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
    window.isPhpLoggedIn = <?= $sync_isPhpLoggedIn ?>;
    window.phpBoundNearAddress = '<?= htmlspecialchars($sync_phpUserWallet, ENT_QUOTES) ?>';
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

    // General cross-contract view call (for FT contracts like USDT/USDC, storage_balance_of, or any other NEAR contract).
    // Re-uses the exact same healthy RPC pool selection, failover, timeout, signal abort, and JSON-RPC 2.0 validation
    // that nearRpcQuery (hub-specific) uses. Other pages (e.g. admin-contract dashboard for on-chain balances)
    // must use this centralized helper instead of duplicating fetch logic.
    window.nearContractView = async function(targetContractId, methodName, args = {}, finality = 'optimistic') {
        if (!targetContractId) {
            return { success: false, error: 'targetContractId is required', status: 'error' };
        }
        const argsBase64 = btoa(unescape(encodeURIComponent(JSON.stringify(args))));

        const payload = {
            jsonrpc: "2.0", id: "soulmd_contract_view", method: "query",
            params: { request_type: "call_function", finality: finality, account_id: targetContractId, method_name: methodName, args_base64: argsBase64 }
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
                    if (!data || data.jsonrpc !== "2.0" || data.id !== "soulmd_contract_view") {
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
                isSignedIn: () => {
                    // If web2 user is logged in and has a bound near_wallet_address in DB,
                    // we consider "signed in" for site purposes only if the selector is also connected
                    // with a matching account (to ensure signing power matches the bound identity).
                    const dbWallet = '<?= htmlspecialchars($sync_phpUserWallet, ENT_QUOTES) ?>';
                    if (dbWallet) {
                        const state = selector.store.getState();
                        const selAccount = state.accounts.length > 0 ? state.accounts[0].accountId : null;
                        return selector.isSignedIn() && selAccount === dbWallet;
                    }
                    return selector.isSignedIn();
                },
                getAccountId: () => {
                    // Priority: if the logged-in user (PHP/DB) has a bound near_wallet_address,
                    // use that as the "current" address for this site (display, actions, etc.).
                    // This prevents the UI from showing a random/wrong accounts[0] from the
                    // wallet selector when the user has explicitly bound one in their profile.
                    const dbWallet = '<?= htmlspecialchars($sync_phpUserWallet, ENT_QUOTES) ?>';
                    if (dbWallet) {
                        return dbWallet;
                    }
                    // Fallback: use the wallet selector's current primary account (not blindly [0]
                    // if we want the "active" one, but accounts[0] is the standard primary).
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
                    functionCall: async (callOpts = {}) => {
                        const callContractId = callOpts.contractId;
                        const methodName = callOpts.methodName;
                        const args = callOpts.args;
                        const gas = callOpts.gas;
                        const attachedDeposit = callOpts.attachedDeposit;
                        const walletCallbackUrl = callOpts.walletCallbackUrl;

                        // Guard: if caller explicitly passed contractId (even for FT payments) but it is empty,
                        // do NOT silently fall back to the default hub contract. This prevents exactly the
                        // bug where ft_transfer_call gets sent to soulmd-hub.near instead of the token contract.
                        if (Object.prototype.hasOwnProperty.call(callOpts, 'contractId') &&
                            (callContractId == null || callContractId === '')) {
                            throw new Error('NEAR wallet: functionCall received empty contractId. For USDT/USDC upgrade payments this must be the token contract (never the app contract).');
                        }

                        const wallet = await selector.wallet();
                        let mGas = (gas || "30000000000000").toString();
                        let mDep = (attachedDeposit || "0").toString();
                        
                        // 雙棲 Action (同時滿足新版與舊版 NAJ 格式)
                        // This format is required to avoid "Unsupported NAJ action" in najActionToInternal
                        // inside the custom wallet-selector-bundle. The clean modern {type, params} alone
                        // triggers the NAJ converter error for some wallets (e.g. the one used by yanshekki.tg).
                        // We still set receiverId explicitly at tx level so the correct token contract
                        // (usdt.tether-token.near or USDC) is the actual target of the transaction.
                        // Note: Some wallet UIs may display the inner args.receiver_id as "To" / destination
                        // for ft_transfer_call, which is the expected logical destination (soulmd-hub.near),
                        // while the contract being called is the token (shown in your debug contractId).
                        const dualAction = {
                            type: "FunctionCall",
                            params: { methodName: methodName, args: args || {}, gas: mGas, deposit: mDep },
                            functionCall: { methodName: methodName, args: args || {}, gas: mGas, deposit: mDep }
                        };

                        const receiverId = (callContractId != null && callContractId !== '') ? callContractId : contractId;
                        return wallet.signAndSendTransaction({
                            receiverId: receiverId,
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

    // 🚀 ROBUST ERROR MESSAGE EXTRACTOR (fixes silent fails on real contract panics like ActionError/ExecutionError)
    // Handles NEAR tx results, wallet selector rejections, and the exact deep structure:
    // { ActionError: { kind: { FunctionCallError: { ExecutionError: "Smart contract panicked: ..." } } } }
    // Also copes with the errorMessage query param carrying raw JSON or the panic string.
    // Extra: swallow "Request validation error" / selector internal validation when tx may have still landed (common with callbackUrl + certain wallets).
    window.getErrorMessage = function(e) {
        if (!e) return 'Unknown error';
        if (typeof e === 'string') {
            if (/request validation error/i.test(e) || /validation error/i.test(e)) {
                return 'Wallet returned a validation notice (transaction may still have succeeded — checking on-chain).';
            }
            return e;
        }

        // 1. Deep NEAR ActionError / ExecutionError / panic extraction (the structure from the user's report)
        const extractNearPanic = (obj, depth = 0) => {
            if (!obj || typeof obj !== 'object' || depth > 6) return null;
            // Direct hits
            if (typeof obj.ExecutionError === 'string') return obj.ExecutionError;
            if (obj.FunctionCallError && typeof obj.FunctionCallError.ExecutionError === 'string')
                return obj.FunctionCallError.ExecutionError;
            if (obj.kind && obj.kind.FunctionCallError && typeof obj.kind.FunctionCallError.ExecutionError === 'string')
                return obj.kind.FunctionCallError.ExecutionError;
            if (obj.ActionError && obj.ActionError.kind)
                return extractNearPanic(obj.ActionError.kind, depth + 1);
            // Common wrappers from wallet / rpc
            if (obj.error && typeof obj.error === 'object') {
                const sub = extractNearPanic(obj.error, depth + 1);
                if (sub) return sub;
            }
            if (obj.cause && typeof obj.cause === 'object') {
                const sub = extractNearPanic(obj.cause, depth + 1);
                if (sub) return sub;
            }
            // Recursive search for any key that looks like it carries the error
            for (const key of Object.keys(obj)) {
                const val = obj[key];
                if (key.toLowerCase().includes('error') || key === 'kind' || key === 'ActionError' || key === 'FunctionCallError') {
                    const sub = extractNearPanic(val, depth + 1);
                    if (sub) return sub;
                }
            }
            return null;
        };

        const nearPanic = extractNearPanic(e);
        if (nearPanic) {
            // Clean up the common prefix for user-friendly alert
            let msg = String(nearPanic);
            msg = msg.replace(/^Smart contract panicked:\s*/i, 'Contract panicked: ');
            return msg.length > 400 ? msg.substring(0, 400) + '...' : msg;
        }

        // 2. Standard paths
        if (e.message && typeof e.message === 'string') return e.message;
        if (e.error) {
            if (typeof e.error === 'string') return e.error;
            if (e.error.message) return e.error.message;
            const sub = extractNearPanic(e.error);
            if (sub) return sub;
        }
        if (typeof e === 'object' && e.toString && !e.message) {
            const s = e.toString();
            if (s && s !== '[object Object]') return s;
        }
        if (e.type) return e.type + (e.message ? ' - ' + e.message : '');
        if (e.reason) return e.reason;
        if (e.details) return e.details;
        if (e.name && e.name !== 'Error') return e.name + (e.message ? ': ' + e.message : '');

        // 3. Last resort: safe stringify (shows the ActionError JSON if nothing else matched)
        try {
            const str = JSON.stringify(e);
            if (str && str !== '{}' && str !== 'null') {
                return str.length > 350 ? str.substring(0, 350) + '...' : str;
            }
        } catch (_) {}
        return String(e);
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