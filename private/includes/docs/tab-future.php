<?php
/**
 * SoulMD Hub - Grand Unified Documentation Sub-module
 * Target: /docs/future (Ecosystem Tokenomics, Financial Ledger & DAO Governance)
 * Layout: Enterprise Deep-Dive AgentFi Tokenomics Whitepaper
 * 🚀 Patched: 100% Parameterized i18n Translation Architecture (No Hardcoded Plain Text).
 * 🚀 Patched: 100% Brand-Agnostic Core Blueprint (Zero 3rd party names).
 */

// 確保此檔案不被直接存取，必須由 docs.php 引入
if (!defined('BASE_URL')) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
?>

<div class="space-y-12 animate-fade-in text-zinc-300">
    
    <div class="border-b border-white/10 pb-6">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-4">
            <?= __('Future Main Title') ?>
        </h2>
        <p class="text-sm sm:text-base leading-relaxed max-w-5xl text-zinc-400">
            <?= __('Future Main Desc') ?>
        </p>
    </div>

    <div class="bg-zinc-950/40 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/40 transition-all duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-chart-pie text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20 text-base sm:text-lg"></i>
            <?= __('Tokenomics Allocation Matrix') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-6 leading-relaxed">
            <?= __('Tokenomics Capital Distribution: The native functional utility asset ($SOUL) is strictly capped at a hard maximum ceiling of 1,000,000,000 units, with zero inflationary minting capabilities written into the base contract. Capital weights are strategically stratified to incentivize open-source prompt modularization, sustain core research and development, and drive continuous on-chain protocol liquidity pools.') ?>
        </p>

        <div class="overflow-x-auto border border-white/10 rounded-2xl mb-6 bg-zinc-950/50 shadow-inner">
            <table class="w-full text-left border-collapse font-mono text-xs sm:text-sm">
                <thead>
                    <tr class="bg-zinc-950 border-b border-white/10 text-zinc-400 uppercase tracking-wider text-[11px]">
                        <th class="p-4 font-bold"><?= __('Allocation Segment') ?></th>
                        <th class="p-4 font-bold"><?= __('Percentage') ?></th>
                        <th class="p-4 font-bold"><?= __('Total Supply (Tokens)') ?></th>
                        <th class="p-4 font-bold"><?= __('Cryptographic Lock & Vesting Schedule') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-zinc-300">
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-4 font-bold text-emerald-400"><i class="fas fa-lightbulb mr-2"></i><?= __('Creator & Ecosystem Mining') ?></td>
                        <td class="p-4 font-bold text-white">30%</td>
                        <td class="p-4 font-bold font-mono text-white">300,000,000</td>
                        <td class="p-4 text-zinc-400 leading-tight"><?= __('Ecosystem Vesting Desc: Allocated directly for prompt-engineering contribution incentives, modular repository forks bonuses, and collaborative template mining loops.') ?></td>
                    </tr>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-4 font-bold text-purple-400"><i class="fas fa-university mr-2"></i><?= __('Platform Treasury Vault') ?></td>
                        <td class="p-4 font-bold text-white">20%</td>
                        <td class="p-4 font-bold font-mono text-white">200,000,000</td>
                        <td class="p-4 text-zinc-400 leading-tight"><?= __('Treasury Vesting Desc: Reserved for continuous core infrastructure scaling, AMM liquidity injection, protocol failover reserve staking, and operating costs.') ?></td>
                    </tr>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-4 font-bold text-blue-400"><i class="fas fa-user-tie mr-2"></i><?= __('Early Backers & Seed Investors') ?></td>
                        <td class="p-4 font-bold text-white">30%</td>
                        <td class="p-4 font-bold font-mono text-white">300,000,000</td>
                        <td class="p-4 text-zinc-400 leading-tight"><?= __('Investors Vesting Desc: Locked under a cryptographically enforced smart contract with a 6-month complete cliff, followed by a 24-month linear vesting grid to protect token distribution velocity.') ?></td>
                    </tr>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="p-4 font-bold text-amber-400"><i class="fas fa-coins mr-2"></i><?= __('Decentralized Staking Rewards') ?></td>
                        <td class="p-4 font-bold text-white">20%</td>
                        <td class="p-4 font-bold font-mono text-white">200,000,000</td>
                        <td class="p-4 text-zinc-400 leading-tight"><?= __('Staking Vesting Desc: Allocated for high-yield validation staking, context summary backup staking, and model lease security deposit staking loops.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-cube text-purple-400 bg-purple-500/10 p-2.5 rounded-xl border border-purple-500/20 text-base sm:text-lg"></i>
            <?= __('Assetization Core Engine') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed"><?= __('Assetization Core Desc') ?></p>
        
        <div class="bg-zinc-900/50 p-5 sm:p-6 rounded-2xl border border-white/5 font-mono text-xs sm:text-sm text-zinc-300 space-y-3 shadow-md">
            <div class="flex items-center gap-2 border-b border-white/5 pb-2 text-purple-400 font-bold">
                <i class="fas fa-database"></i> <?= __('NFT Structure Matrix') ?>
            </div>
            <p class="text-zinc-400 leading-relaxed"><?= __('NFT Structure Desc') ?></p>
            <pre class="bg-zinc-950 p-4 rounded-xl text-purple-300 overflow-x-auto border border-white/5 leading-tight select-all">
class TokenMetadata {
    title: string;       // <?= __('Token Identification Asset Name') ?>
    description: string; // <?= __('On-Chain Public Decentralized Bio Description') ?>
    extra: string;       // 🔒 <?= __('Cryptographic Fingerprint Hash: sha256(content + random_salt)') ?>
    reference: string;   // <?= __('Authoritative Platform Verification API Pointer') ?>
    creator_id: string;  // <?= __('Permanent Creator Account Address for Composable Royalty Routing') ?>
}</pre>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-purple-500/20 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-white mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-handshake text-indigo-400 bg-indigo-500/10 p-2.5 rounded-xl border border-indigo-500/20 text-base sm:text-lg"></i>
            <?= __('Decentralized Rental Architecture') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-6 leading-relaxed"><?= __('Decentralized Rental Desc') ?></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/50 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-md">
                <h4 class="text-white font-bold text-sm sm:text-base mb-3 flex items-center gap-2 font-mono">
                    <i class="fas fa-user-secret text-indigo-400 text-xs"></i> <?= __('Blackbox Exec Matrix') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono bg-black/30 p-3 rounded-xl border border-white/5">
                    <?= __('Blackbox Exec Desc') ?>
                </p>
            </div>
            
            <div class="bg-zinc-900/50 border border-white/5 p-5 sm:p-6 rounded-2xl shadow-md">
                <h4 class="text-white font-bold text-sm sm:text-base mb-3 flex items-center gap-2 font-mono">
                    <i class="fas fa-network-wired text-indigo-400 text-xs"></i> <?= __('Royalty Distribution Tree') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono bg-black/30 p-3 rounded-xl border border-white/5">
                    <?= __('Royalty Tree Desc') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-b from-zinc-950 to-indigo-950/20 border border-purple-500/20 rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-48 h-48 bg-purple-500/10 blur-3xl rounded-full pointer-events-none"></div>
        
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 bg-purple-500/10 border border-purple-500/30 rounded-2xl flex items-center justify-center text-purple-400 shrink-0 shadow-lg shadow-purple-500/10">
                <i class="fas fa-fire-alt text-2xl animate-pulse"></i>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-indigo-400">
                <?= __('Deflationary Spiral Title') ?>
            </h3>
        </div>
        
        <p class="text-sm text-zinc-300 mb-8 leading-relaxed font-medium">
            <?= __('Deflationary Spiral Desc') ?>
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-black/40 border border-purple-500/10 p-5 rounded-2xl flex flex-col justify-between">
                <div>
                    <div class="text-purple-400 mb-3 font-bold text-sm flex items-center gap-2">
                        <i class="fas fa-hammer"></i> <?= __('Treasury Inflow Channels') ?> I
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                        <?= __('Inflow Mint Tax') ?>
                    </p>
                </div>
            </div>
            
            <div class="bg-black/40 border border-purple-500/10 p-5 rounded-2xl flex flex-col justify-between">
                <div>
                    <div class="text-indigo-400 mb-3 font-bold text-sm flex items-center gap-2">
                        <i class="fas fa-percentage"></i> <?= __('Treasury Inflow Channels') ?> II
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed font-mono">
                        <?= __('Inflow Market Tax') ?>
                    </p>
                </div>
            </div>
            
            <div class="bg-black/40 border border-purple-500/30 p-5 rounded-2xl md:transform md:-translate-y-2 relative overflow-hidden flex flex-col justify-between shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-tr from-purple-500/5 to-transparent"></div>
                <div class="relative z-10">
                    <div class="text-pink-400 mb-3 font-bold text-sm flex items-center gap-2 animate-pulse">
                        <i class="fas fa-sync fa-spin"></i> <?= __('Automated Market Maker Loop') ?>
                    </div>
                    <p class="text-xs sm:text-sm text-zinc-200 font-medium leading-relaxed font-mono">
                        <?= __('Inflow AMM Execution') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-zinc-950/40 border border-white/5 rounded-3xl p-6 sm:p-8 md:p-10 shadow-inner hover:border-amber-500/20 transition-all duration-300">
        <h3 class="text-xl sm:text-2xl font-bold text-amber-400 mb-4 flex items-center gap-3 select-none">
            <i class="fas fa-users-cog bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20 text-base sm:text-lg"></i>
            <?= __('Decentralized DAO Governance Direction') ?>
        </h3>
        <p class="text-sm text-zinc-400 mb-8 leading-relaxed">
            <?= __('DAO Governance Core Desc: To ensure the platform scales with mathematical antifragility, system parameter controls are fully transferred to a native token-gated DAO structure. $SOUL token holders form the ultimate decision-making body, directly adjusting contract weights and approving upgrades via proportional snapshot voting arrays.') ?>
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-md hover:border-amber-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-gavel text-amber-400 text-xs"></i> <?= __('Parameter Optimization Proposals') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Parameter Proposal Desc: Token holders vote linearly on system runtime variables. This includes modifying the platform storage deposit tiers, adjusting secondary buyout royalties percentages, and shifting memory window summaries thresholds across global subscription accounts dynamically.') ?>
                </p>
            </div>
            <div class="bg-zinc-900/40 border border-white/5 p-6 rounded-2xl shadow-md hover:border-amber-500/20 transition-colors">
                <h4 class="text-white font-bold text-base mb-3 flex items-center gap-2">
                    <i class="fas fa-code text-amber-400 text-xs"></i> <?= __('Contract Upgradeability & TEE Execution') ?>
                </h4>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                    <?= __('Vesting Upgradeability Desc: Future core contract optimizations or migrations to hardware-isolated Trusted Execution Environments (TEE) require explicit DAO threshold approvals. No single administrative root key can manipulate model code paths without a verifiable consensus quorum.') ?>
                </p>
            </div>
        </div>
    </div>

</div>