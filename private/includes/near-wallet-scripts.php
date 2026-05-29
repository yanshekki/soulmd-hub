<?php
/**
 * SoulMD Hub - Shared NEAR Wallet Connection Script
 * (NEAR Wallet Selector UI Edition - Supports Meteor, Sender, Here, MyNearWallet)
 */
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@near-wallet-selector/modal-ui@8.9.12/styles.css">
<script src="https://cdn.jsdelivr.net/npm/near-api-js@1.1.0/dist/near-api-js.min.js"></script>

<script type="module">
    import { setupWalletSelector } from "https://esm.sh/@near-wallet-selector/core@8.9.12";
    import { setupModal } from "https://esm.sh/@near-wallet-selector/modal-ui@8.9.12";
    import { setupMeteorWallet } from "https://esm.sh/@near-wallet-selector/meteor-wallet@8.9.12";
    import { setupSender } from "https://esm.sh/@near-wallet-selector/sender@8.9.12";
    import { setupHereWallet } from "https://esm.sh/@near-wallet-selector/here-wallet@8.9.12";
    import { setupMyNearWallet } from "https://esm.sh/@near-wallet-selector/my-near-wallet@8.9.12";

    window.nearHubWalletSelector = null;
    window.nearHubWalletModal = null;
    window.nearHubWalletWrapper = null;

    window.initNearWallet = async function() {
        if (window.nearHubWalletWrapper) return window.nearHubWalletWrapper;

        // 初始化 Wallet Selector
        const selector = await setupWalletSelector({
            network: "mainnet",
            modules: [
                setupMeteorWallet(),
                setupSender(),
                setupHereWallet(),
                setupMyNearWallet()
            ]
        });

        // 初始化 Modal 彈窗
        const modal = setupModal(selector, {
            contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>"
        });

        // 監聽擴充錢包 (如 Meteor, Sender) 的登入事件，觸發重新整理以同步後端 Session
        selector.on("signedIn", (e) => {
            if (window.location.pathname.includes('/login')) {
                const accountId = e.accounts[0].accountId;
                if (!window.location.search.includes('account_id')) {
                    window.location.href = window.location.pathname + "?account_id=" + accountId;
                }
            }
        });

        window.nearHubWalletSelector = selector;
        window.nearHubWalletModal = modal;

        // 封裝 Wrapper：保持與舊版 near-api-js WalletConnection 的方法名稱 100% 一致
        window.nearHubWalletWrapper = {
            isSignedIn: () => selector.isSignedIn(),
            getAccountId: () => {
                if (!selector.isSignedIn()) return null;
                return selector.store.getState().accounts[0].accountId;
            },
            requestSignIn: () => {
                modal.show(); // 喚起精美的多錢包選擇彈窗
            },
            signOut: async () => {
                const wallet = await selector.wallet();
                return wallet.signOut();
            },
            account: () => ({
                functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                    const wallet = await selector.wallet();
                    return wallet.signAndSendTransaction({
                        signerId: window.nearHubWalletWrapper.getAccountId(),
                        receiverId: contractId,
                        actions: [{
                            type: "FunctionCall",
                            params: { methodName, args, gas, deposit: attachedDeposit }
                        }],
                        callbackUrl: walletCallbackUrl
                    });
                }
            }),
            requestSignTransactions: async ({ transactions, callbackUrl }) => {
                const wallet = await selector.wallet();
                const formattedTxs = transactions.map(tx => {
                    return {
                        signerId: window.nearHubWalletWrapper.getAccountId(),
                        receiverId: tx.receiverId,
                        actions: tx.actions.map(action => {
                            if (action.functionCall) {
                                return {
                                    type: "FunctionCall",
                                    params: {
                                        methodName: action.functionCall.methodName,
                                        args: action.functionCall.args,
                                        gas: action.functionCall.gas.toString(),
                                        deposit: action.functionCall.deposit.toString()
                                    }
                                };
                            }
                            return action;
                        })
                    };
                });
                return wallet.signAndSendTransactions({ transactions: formattedTxs, callbackUrl });
            }
        };

        return window.nearHubWalletWrapper;
    };
</script>