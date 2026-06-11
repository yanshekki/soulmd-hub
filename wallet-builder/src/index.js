import './polyfills.js';

import { setupWalletSelector } from "@near-wallet-selector/core";
import { setupModal } from "@near-wallet-selector/modal-ui-js";

import { setupMeteorWallet } from "@near-wallet-selector/meteor-wallet";
import { setupSender } from "@near-wallet-selector/sender";
import { setupHereWallet } from "@near-wallet-selector/here-wallet";
import { setupNightly } from "@near-wallet-selector/nightly";
import { setupLedger } from "@near-wallet-selector/ledger";
import { setupBitgetWallet } from "@near-wallet-selector/bitget-wallet";

import * as nearApi from "near-api-js";
import "@near-wallet-selector/modal-ui-js/styles.css";

// 暴露給 PHP 呼叫
window.nearApi = nearApi;

window.initWalletSelectorUI = async function(networkId, contractId) {
    const selector = await setupWalletSelector({
        network: networkId,
        modules: [
            setupMeteorWallet(),
            setupSender(),
            setupHereWallet(),
            setupNightly(),
            setupBitgetWallet(),
            setupLedger()
        ]
    });

    const modal = setupModal(selector, {
        contractId: contractId,
        theme: 'dark'
    });

    return { selector, modal };
};