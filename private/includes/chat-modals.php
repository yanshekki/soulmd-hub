<?php
/**
 * SoulMD Hub - Chat Modals Component
 * Included dynamically in chat.php
 * Requires variables: $isExpired
 */
?>

<div id="image-viewer-modal" class="hidden fixed inset-0 z-[300] bg-black/95 flex items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeImageModal()">
    <button type="button" class="absolute top-6 right-6 text-white hover:text-emerald-400 text-3xl transition focus:outline-none"><i class="fas fa-times"></i></button>
    <img id="image-viewer-img" src="" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
</div>

<div id="paywall-modal" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center z-[200] p-4 backdrop-blur-md opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border <?= $isExpired ? 'border-red-500/40 shadow-red-500/5' : 'border-emerald-500/30 shadow-emerald-500/5' ?> rounded-3xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        
        <div class="p-5 sm:p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/50 shrink-0 select-none">
            <div>
                <h3 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                    <?= $isExpired ? 'Your Premium Subscription has Expired! ⚠️' : 'Unlock Full AI Power 🚀' ?>
                </h3>
                <p class="text-xs sm:text-sm text-zinc-400 mt-1 leading-tight">
                    <?= $isExpired ? 'Your access window has closed. Please renew your plan to restore active token clusters.' : 'You\'ve reached the free trial limit or tried to access a premium feature.' ?>
                </p>
            </div>
            <button type="button" onclick="closePaywall()" class="text-zinc-400 hover:text-white transition pl-2 focus:outline-none"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="p-5 sm:p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-950/30 overflow-y-auto custom-scrollbar flex-grow">
            
            <div class="bg-zinc-900 border border-white/10 rounded-3xl p-5 sm:p-6 flex flex-col hover:border-emerald-400/50 transition justify-between min-h-[400px] md:min-h-0">
                <div>
                    <div class="text-emerald-400 text-xs font-bold tracking-widest uppercase mb-1">VIP Plan</div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-2">$<?= PRICE_VIP_MONTHLY ?> <span class="text-sm text-zinc-500 font-normal">/mo</span></div>
                    <p class="text-xs sm:text-sm text-zinc-400 mb-6 pb-6 border-b border-white/10 leading-relaxed">Perfect for daily tasks and unrestricted standard AI conversations.</p>
                    <ul class="space-y-3 mb-6 text-xs sm:text-sm text-zinc-300">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0"></i> <span><b>Unlimited</b> standard messages</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0"></i> <span>Up to <b><?= number_format(VIP_MAX_INPUT_CHARS) ?></b> characters</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0"></i> <span><b>Vision AI</b>: Snapshot upload features</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0"></i> <span>Smart context sliding snapshots</span></li>
                    </ul>
                </div>
                <div class="pt-4 border-t border-white/5 mt-auto">
                    <a href="/upgrade" class="w-full block py-3 <?= $isExpired ? 'bg-zinc-800 hover:bg-red-500 hover:text-zinc-950' : 'bg-zinc-800 hover:bg-zinc-700' ?> text-white font-bold rounded-xl text-center text-sm transition shadow-md">
                        <?= $isExpired ? '<i class="fas fa-sync-alt mr-1"></i> Renew VIP Pass' : 'Upgrade to VIP' ?>
                    </a>
                </div>
            </div>

            <div class="bg-gradient-to-b from-emerald-900/40 to-zinc-900 border border-emerald-500/50 rounded-3xl p-5 sm:p-6 flex flex-col justify-between relative shadow-2xl min-h-[400px] md:min-h-0 md:transform md:-translate-y-1">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-emerald-500 text-zinc-950 text-[9px] font-black px-3 py-0.5 rounded-full uppercase tracking-widest shadow-md">Most Powerful</div>
                <div>
                    <div class="text-white text-xs font-bold tracking-widest uppercase mb-1 flex items-center gap-1.5"><i class="fas fa-fire text-amber-500"></i> PRO Plan</div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white mb-2">$<?= PRICE_PRO_MONTHLY ?> <span class="text-sm text-emerald-500/50 font-normal">/mo</span></div>
                    <p class="text-xs sm:text-sm text-emerald-100/70 mb-6 pb-6 border-b border-emerald-500/20 leading-relaxed">Unlock our ultimate Elite Reasoning Engine for complex logic and coding tasks.</p>
                    <ul class="space-y-3 mb-6 text-xs sm:text-sm text-zinc-200">
                        <li class="flex items-start gap-2"><i class="fas fa-star text-amber-400 mt-0.5 shrink-0"></i> <span><b>Elite Reasoning Engine</b> Brain Access</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0"></i> <span><b>Unlimited</b> advanced reasoning slots</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0"></i> <span>Massive <b><?= number_format(PRO_MAX_INPUT_CHARS) ?></b> characters</span></li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0"></i> <span>High snapshot memory snap (30 layers)</span></li>
                    </ul>
                </div>
                <div class="pt-4 border-t border-emerald-500/20 mt-auto">
                    <a href="/upgrade" class="w-full block py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl text-center text-sm transition shadow-lg">
                        <?= $isExpired ? '<i class="fas fa-sync-alt mr-1"></i> Renew PRO Pass' : 'Get PRO Access' ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>