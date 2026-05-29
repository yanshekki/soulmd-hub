<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * (Production-Grade Local Bundled Selector Matrix with Modal Centering Fix)
 */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@near-wallet-selector/modal-ui@8.9.12/styles.css">

<style>
#near-wallet-selector-modal {
    z-index: 99999 !important;
}
#near-wallet-selector-modal .nws-modal-wrapper {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: rgba(0, 0, 0, 0.75) !important;
}
#near-wallet-selector-modal .nws-modal {
    margin: auto !important;
    max-height: 90vh !important;
    overflow-y: auto !important;
    position: relative !important;
}
#near-wallet-selector-modal .nws-modal-title-wrapper {
    display: none !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/buffer@6.0.3/index.min.js"></script>

<script>
    // 🚨 終極防護：喺加載 wallet.bundle.js 之前，強行建立假 Node.js 環境
    window.global = window;
    window.Buffer = window.Buffer || buffer.Buffer;
    window.process = {
        env: {
            NODE_ENV: 'production',
            DEFAULT_FINALITY: 'near-final'
        },
        version: 'v18.0.0',
        nextTick: function(cb) { setTimeout(cb, 0); }
    };

    window.nearHubWalletWrapper = null;
    window._nearWalletInitPromise = new Promise(resolve => {
        window._nearWalletInitResolver = resolve;
    });

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;
        return await window._nearWalletInitPromise;
    };
</script>

<script src="/js/wallet.bundle.js?v=3"></script>

<script>
    document.addEventListener("DOMContentLoaded", async () => {
        if (window.initWalletSelectorMatrix) {
            const contractId = "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>";
            await window.initWalletSelectorMatrix(contractId);
        }
    });
</script>