import { setupWalletSelector } from "@near-wallet-selector/core";
import { setupModal } from "@near-wallet-selector/modal-ui";
import { setupMeteorWallet } from "@near-wallet-selector/meteor-wallet";
import { setupSender } from "@near-wallet-selector/sender";
import { setupHereWallet } from "@near-wallet-selector/here-wallet";
import { setupMyNearWallet } from "@near-wallet-selector/my-near-wallet";

async function initWalletSelectorMatrix(contractId) {
    try {
        const selector = await setupWalletSelector({
            network: "mainnet",
            modules: [
                setupMeteorWallet(),
                setupSender(),
                setupHereWallet(),
                setupMyNearWallet()
            ]
        });

        const modal = setupModal(selector, { contractId: contractId });

        selector.on("signedIn", (e) => {
            if (window.location.pathname.includes('/login')) {
                const accountId = e.accounts[0].accountId;
                if (!window.location.search.includes('account_id')) {
                    window.location.href = window.location.pathname + "?account_id=" + accountId;
                }
            }
        });

        const wrapper = {
            isSignedIn: () => selector.isSignedIn(),
            getAccountId: () => {
                if (!selector.isSignedIn()) return null;
                return selector.store.getState().accounts[0].accountId;
            },
            requestSignIn: () => { modal.show(); },
            signOut: async () => {
                const wallet = await selector.wallet();
                return wallet.signOut();
            },
            account: () => ({
                functionCall: async ({ contractId, methodName, args, gas, attachedDeposit, walletCallbackUrl }) => {
                    const wallet = await selector.wallet();
                    return wallet.signAndSendTransaction({
                        signerId: wrapper.getAccountId(),
                        receiverId: contractId,
                        actions: [{
                            type: "FunctionCall",
                            params: { methodName, args, gas: gas.toString(), deposit: attachedDeposit.toString() }
                        }],
                        callbackUrl: walletCallbackUrl
                    });
                }
            }),
            requestSignTransactions: async ({ transactions, callbackUrl }) => {
                const wallet = await selector.wallet();
                const formattedTxs = transactions.map(tx => ({
                    signerId: wrapper.getAccountId(),
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
                }));
                return wallet.signAndSendTransactions({ transactions: formattedTxs, callbackUrl });
            }
        };

        window.nearHubWalletSelector = selector;
        window.nearHubWalletModal = modal;
        window.nearHubWalletWrapper = wrapper;

        if (window._nearWalletInitResolver) {
            window._nearWalletInitResolver(wrapper);
        }
        return wrapper;
    } catch (err) {
        console.error("Core Wallet Bundle Crash:", err);
    }
}
window.initWalletSelectorMatrix = initWalletSelectorMatrix;