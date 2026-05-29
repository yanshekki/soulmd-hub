<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * Can be included anywhere that needs Web3 Wallet interactions.
 */
?>
<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>
<script>
    const { connect, keyStores, WalletConnection, utils } = nearApi;
    const nearConfig = {
        networkId: "mainnet",
        keyStore: new keyStores.BrowserLocalStorageKeyStore(),
        nodeUrl: "https://rpc.mainnet.near.org",
        walletUrl: "https://app.mynearwallet.com",
        helperUrl: "https://helper.mainnet.near.org",
        explorerUrl: "https://explorer.mainnet.near.org",
    };

    let nearHubConnection = null;
    let nearHubWallet = null;

    // 全局共用的初始化函數
    async function initNearWallet() {
        if (!nearHubConnection) {
            nearHubConnection = await connect(nearConfig);
            nearHubWallet = new WalletConnection(nearHubConnection, 'soulmd_hub');
        }
        return nearHubWallet;
    }
</script>