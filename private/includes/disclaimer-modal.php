<?php
/**
 * SoulMD Hub - Critical Legal Disclaimer Modal
 * Included dynamically in chat.php
 * (Dynamic i18n Internationalization Edition)
 *
 * Stacking: must paint above sticky header (z-1000) and site footer.
 * Prefer including this AFTER footer.php in chat.php so DOM order is safe.
 */

// 🌍 載入免責聲明組件專屬語言包
loadTranslations('disclaimer-modal');
?>
<div id="disclaimer-modal"
     class="hidden fixed inset-0 flex items-center justify-center p-4 sm:p-6 backdrop-blur-md"
     style="z-index: 99999; background-color: rgba(0,0,0,0.92);"
     role="dialog"
     aria-modal="true"
     aria-labelledby="disclaimer-modal-title">
    <div class="bg-zinc-900 border border-emerald-500/30 rounded-3xl max-w-2xl w-full max-h-[min(90dvh,calc(100dvh-2rem))] flex flex-col overflow-hidden shadow-2xl"
         style="position: relative; z-index: 1;">
        
        <div class="p-5 sm:p-6 border-b border-white/10 bg-zinc-950/40 shrink-0">
            <h3 id="disclaimer-modal-title" class="text-xl font-bold tracking-tight text-amber-400 flex items-center gap-2">
                <i class="fas fa-gavel" aria-hidden="true"></i> <?= __('Disclaimer Title') ?>
            </h3>
            <p class="text-xs text-zinc-500 mt-1"><?= __('Disclaimer Subtitle') ?></p>
        </div>
        
        <div class="p-5 sm:p-6 overflow-y-auto space-y-5 text-xs text-zinc-300 leading-relaxed custom-scrollbar flex-grow bg-black/20 min-h-0">
            <div>
                <p class="font-bold text-white text-sm mb-1"><?= __('Disclaimer H1') ?></p>
                <p><?= __('Disclaimer P1') ?></p>
            </div>
            
            <div>
                <p class="font-bold text-white text-sm mb-1"><?= __('Disclaimer H2') ?></p>
                <p><?= __('Disclaimer P2') ?></p>
            </div>

            <div>
                <p class="font-bold text-white text-sm mb-1"><?= __('Disclaimer H3') ?></p>
                <p><?= __('Disclaimer P3') ?></p>
            </div>

            <div>
                <p class="font-bold text-white text-sm mb-1"><?= __('Disclaimer H4') ?></p>
                <p><?= __('Disclaimer P4') ?></p>
            </div>

            <div>
                <p class="font-bold text-white text-sm mb-1"><?= __('Disclaimer H5') ?></p>
                <p><?= __('Disclaimer P5', ['chars' => number_format($maxInputChars)]) ?></p>
            </div>
        </div>

        <div class="p-5 sm:p-6 border-t border-white/10 bg-zinc-900 flex flex-col-reverse sm:flex-row justify-end gap-3 shrink-0">
            <button type="button" onclick="declineDisclaimer()" class="px-5 py-2.5 border border-white/10 rounded-xl text-sm font-medium text-zinc-300 hover:bg-white/5 transition"><?= __('Decline & Exit') ?></button>
            <button type="button" onclick="acceptDisclaimer()" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold text-sm shadow-lg hover:bg-emerald-400 transition transform hover:scale-105 duration-200"><?= __('I Understand & Agree') ?></button>
        </div>
    </div>
</div>
