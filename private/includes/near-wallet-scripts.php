<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * (Production-Grade Local Bundled Selector Matrix)
 */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@near-wallet-selector/modal-ui@8.9.12/styles.css">
<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>

<script src="/js/wallet.bundle.js"></script>

<script>
    // 建立監聽鎖定 Promise 矩陣，確保多線程加載時前端絕不噴出 "is not defined"
    window.nearHubWalletWrapper = null;
    window._nearWalletInitPromise = new Promise(resolve => {
        window._nearWalletInitResolver = resolve;
    });

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;
        return await window._nearWalletInitPromise;
    };

    // 頁面加載完成後，立刻啟動本地編譯的 Wallet Selector Matrix
    document.addEventListener("DOMContentLoaded", async () => {
        if (window.initWalletSelectorMatrix) {
            const contractId = "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>";
            await window.initWalletSelectorMatrix(contractId);
        }
    });
</script>