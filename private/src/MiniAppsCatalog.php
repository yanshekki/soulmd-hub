<?php
/**
 * SoulMD Hub - Curated Mini Apps catalog (form-driven tools).
 * Each app has comma-separated search_keywords (phrases may contain spaces).
 * Public non-NFT souls are loaded via OR keyword match. Users pick a soul then /chat.
 */

class MiniAppsCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function allRaw(): array
    {
        return [
            [
                'slug' => 'name-advisor',
                'icon' => 'fa-signature',
                'category' => 'destiny',
                'title_key' => 'app_name_advisor_title',
                'desc_key' => 'app_name_advisor_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 10,
                'enabled' => true,
                'badge' => 'popular',
                // Comma-separated phrases (spaces inside a phrase are preserved — do NOT split on space)
                'search_keywords' => '改名,命名,姓名,起名,姓名學,命理姓名',
                'builtin_prompt' => "You are an expert Chinese naming consultant (改名 / 命名顧問). Use Five Elements (五行), character structure, and Cantonese/Mandarin phonetics when relevant. Be concrete: propose ranked name options with brief rationale. Prefer tables when listing multiple names. Respond in the user's language.",
                'fields' => [
                    ['name' => 'surname', 'type' => 'text', 'label_key' => 'field_surname', 'placeholder_key' => 'ph_surname', 'required' => true, 'maxlength' => 20],
                    ['name' => 'gender', 'type' => 'select', 'label_key' => 'field_gender', 'required' => true, 'options' => [
                        ['value' => 'female', 'label_key' => 'opt_female'],
                        ['value' => 'male', 'label_key' => 'opt_male'],
                        ['value' => 'neutral', 'label_key' => 'opt_neutral'],
                    ]],
                    ['name' => 'birth_datetime', 'type' => 'text', 'label_key' => 'field_birth_datetime', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'preferences', 'type' => 'textarea', 'label_key' => 'field_name_preferences', 'placeholder_key' => 'ph_name_preferences', 'required' => false, 'maxlength' => 200],
                    ['name' => 'count', 'type' => 'select', 'label_key' => 'field_name_count', 'required' => false, 'options' => [
                        ['value' => '3', 'label_key' => 'opt_count_3'],
                        ['value' => '5', 'label_key' => 'opt_count_5'],
                    ]],
                ],
            ],
            [
                'slug' => 'feng-shui',
                'icon' => 'fa-compass',
                'category' => 'destiny',
                'title_key' => 'app_feng_shui_title',
                'desc_key' => 'app_feng_shui_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 20,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '風水,feng shui,布局,家宅',
                'builtin_prompt' => "You are a practical feng shui consultant (風水顧問). Give actionable, safety-aware advice for homes/offices. Structure: overall assessment, priority fixes, and what to avoid. Respond in the user's language. Avoid absolute medical/financial claims.",
                'fields' => [
                    ['name' => 'space_type', 'type' => 'select', 'label_key' => 'field_space_type', 'required' => true, 'options' => [
                        ['value' => 'apartment', 'label_key' => 'opt_apartment'],
                        ['value' => 'house', 'label_key' => 'opt_house'],
                        ['value' => 'office', 'label_key' => 'opt_office'],
                        ['value' => 'shop', 'label_key' => 'opt_shop'],
                    ]],
                    ['name' => 'orientation', 'type' => 'text', 'label_key' => 'field_orientation', 'placeholder_key' => 'ph_orientation', 'required' => false, 'maxlength' => 40],
                    ['name' => 'floor_layout', 'type' => 'textarea', 'label_key' => 'field_floor_layout', 'placeholder_key' => 'ph_floor_layout', 'required' => true, 'maxlength' => 300],
                    ['name' => 'concern', 'type' => 'textarea', 'label_key' => 'field_feng_concern', 'placeholder_key' => 'ph_feng_concern', 'required' => true, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'wedding-date',
                'icon' => 'fa-heart',
                'category' => 'life',
                'title_key' => 'app_wedding_date_title',
                'desc_key' => 'app_wedding_date_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 30,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '擇日,結婚,婚嫁,合婚,wedding date,吉日',
                'builtin_prompt' => "You are a traditional Chinese auspicious date consultant for weddings (擇日結婚). Propose several candidate dates with short rationale (天干地支 / 宜忌 style when useful). Be respectful and note cultural tradition is advisory only. Respond in the user's language.",
                'fields' => [
                    ['name' => 'person_a_birth', 'type' => 'text', 'label_key' => 'field_person_a_birth', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'person_b_birth', 'type' => 'text', 'label_key' => 'field_person_b_birth', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'preferred_months', 'type' => 'text', 'label_key' => 'field_preferred_months', 'placeholder_key' => 'ph_preferred_months', 'required' => false, 'maxlength' => 60],
                    ['name' => 'region', 'type' => 'text', 'label_key' => 'field_region', 'placeholder_key' => 'ph_region', 'required' => false, 'maxlength' => 40],
                    ['name' => 'notes', 'type' => 'textarea', 'label_key' => 'field_wedding_notes', 'placeholder_key' => 'ph_wedding_notes', 'required' => false, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'virtual-companion',
                'icon' => 'fa-heart',
                'category' => 'emotion',
                'title_key' => 'app_virtual_companion_title',
                'desc_key' => 'app_virtual_companion_desc',
                'sort_order' => 40,
                'enabled' => true,
                'badge' => 'hot',
                'search_keywords' => '虛擬情人,虛擬 情人,companion,lover,陪伴,roleplay',
                'builtin_prompt' => "You are a warm, respectful virtual companion roleplay partner. Stay in character, match the requested tone, and keep interactions safe/consensual for adults. Do not claim to be a real human. Respond in the user's language.",
                'fields' => [
                    ['name' => 'persona_style', 'type' => 'select', 'label_key' => 'field_persona_style', 'required' => true, 'options' => [
                        ['value' => 'gentle', 'label_key' => 'opt_gentle'],
                        ['value' => 'playful', 'label_key' => 'opt_playful'],
                        ['value' => 'intellectual', 'label_key' => 'opt_intellectual'],
                        ['value' => 'protective', 'label_key' => 'opt_protective'],
                    ]],
                    ['name' => 'tone', 'type' => 'select', 'label_key' => 'field_tone', 'required' => true, 'options' => [
                        ['value' => 'cantonese', 'label_key' => 'opt_cantonese'],
                        ['value' => 'mandarin', 'label_key' => 'opt_mandarin'],
                        ['value' => 'english', 'label_key' => 'opt_english'],
                    ]],
                    ['name' => 'scenario', 'type' => 'textarea', 'label_key' => 'field_scenario', 'placeholder_key' => 'ph_scenario', 'required' => true, 'maxlength' => 300],
                    ['name' => 'message', 'type' => 'textarea', 'label_key' => 'field_first_message', 'placeholder_key' => 'ph_first_message', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'daily-fortune',
                'icon' => 'fa-star',
                'category' => 'destiny',
                'title_key' => 'app_daily_fortune_title',
                'desc_key' => 'app_daily_fortune_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 50,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '塔羅,命理,星座,玄學,紫微,占卜',
                'builtin_prompt' => "You are a concise daily fortune (今日運勢) advisor. Cover overall luck, career, relationships, health tips, and a lucky color/number. Keep it uplifting and non-fatalistic. Respond in the user's language.",
                'fields' => [
                    ['name' => 'birth_date', 'type' => 'text', 'label_key' => 'field_birth_date', 'placeholder_key' => 'ph_birth_date', 'required' => true, 'maxlength' => 20],
                    ['name' => 'focus', 'type' => 'select', 'label_key' => 'field_fortune_focus', 'required' => false, 'options' => [
                        ['value' => 'general', 'label_key' => 'opt_focus_general'],
                        ['value' => 'career', 'label_key' => 'opt_focus_career'],
                        ['value' => 'love', 'label_key' => 'opt_focus_love'],
                        ['value' => 'wealth', 'label_key' => 'opt_focus_wealth'],
                    ]],
                    ['name' => 'extra', 'type' => 'text', 'label_key' => 'field_fortune_extra', 'placeholder_key' => 'ph_fortune_extra', 'required' => false, 'maxlength' => 80],
                ],
            ],
            // --- P0 apps from public soul inventory research ---
            [
                'slug' => 'legal-review',
                'icon' => 'fa-gavel',
                'category' => 'legal',
                'title_key' => 'app_legal_review_title',
                'desc_key' => 'app_legal_review_desc',
                'disclaimer_key' => 'disclaimer_legal',
                'sort_order' => 60,
                'enabled' => true,
                'badge' => 'popular',
                'search_keywords' => '法律,合約,合規,律師,法務',
                'builtin_prompt' => 'You are a careful legal information assistant. Clarify issues, list risks and questions to ask a licensed lawyer. Never claim to give formal legal advice. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'region', 'type' => 'text', 'label_key' => 'field_legal_region', 'placeholder_key' => 'ph_legal_region', 'required' => true, 'maxlength' => 40],
                    ['name' => 'doc_type', 'type' => 'select', 'label_key' => 'field_legal_doc_type', 'required' => true, 'options' => [
                        ['value' => 'contract', 'label_key' => 'opt_legal_contract'],
                        ['value' => 'family', 'label_key' => 'opt_legal_family'],
                        ['value' => 'medical', 'label_key' => 'opt_legal_medical'],
                        ['value' => 'compliance', 'label_key' => 'opt_legal_compliance'],
                        ['value' => 'other', 'label_key' => 'opt_legal_other'],
                    ]],
                    ['name' => 'party_role', 'type' => 'select', 'label_key' => 'field_legal_party', 'required' => false, 'options' => [
                        ['value' => 'a', 'label_key' => 'opt_legal_party_a'],
                        ['value' => 'b', 'label_key' => 'opt_legal_party_b'],
                        ['value' => 'neutral', 'label_key' => 'opt_legal_party_neutral'],
                    ]],
                    ['name' => 'question', 'type' => 'textarea', 'label_key' => 'field_legal_question', 'placeholder_key' => 'ph_legal_question', 'required' => true, 'maxlength' => 500],
                    ['name' => 'deadline', 'type' => 'text', 'label_key' => 'field_legal_deadline', 'placeholder_key' => 'ph_legal_deadline', 'required' => false, 'maxlength' => 40],
                ],
            ],
            [
                'slug' => 'sales-coach',
                'icon' => 'fa-handshake',
                'category' => 'career',
                'title_key' => 'app_sales_coach_title',
                'desc_key' => 'app_sales_coach_desc',
                'sort_order' => 70,
                'enabled' => true,
                'badge' => 'hot',
                'search_keywords' => '銷售,成交,漏斗,異議,話術',
                'builtin_prompt' => 'You are a practical sales coach. Give concrete talk tracks, objection handling steps, and next actions. Be concise and role-play ready. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'product', 'type' => 'text', 'label_key' => 'field_sales_product', 'placeholder_key' => 'ph_sales_product', 'required' => true, 'maxlength' => 80],
                    ['name' => 'audience', 'type' => 'text', 'label_key' => 'field_sales_audience', 'placeholder_key' => 'ph_sales_audience', 'required' => true, 'maxlength' => 80],
                    ['name' => 'channel', 'type' => 'select', 'label_key' => 'field_sales_channel', 'required' => true, 'options' => [
                        ['value' => 'call', 'label_key' => 'opt_sales_call'],
                        ['value' => 'meeting', 'label_key' => 'opt_sales_meeting'],
                        ['value' => 'live', 'label_key' => 'opt_sales_live'],
                        ['value' => 'chat', 'label_key' => 'opt_sales_chat'],
                        ['value' => 'email', 'label_key' => 'opt_sales_email'],
                    ]],
                    ['name' => 'objection', 'type' => 'textarea', 'label_key' => 'field_sales_objection', 'placeholder_key' => 'ph_sales_objection', 'required' => true, 'maxlength' => 400],
                    ['name' => 'goal', 'type' => 'select', 'label_key' => 'field_sales_goal', 'required' => false, 'options' => [
                        ['value' => 'close', 'label_key' => 'opt_sales_close'],
                        ['value' => 'demo', 'label_key' => 'opt_sales_demo'],
                        ['value' => 'followup', 'label_key' => 'opt_sales_followup'],
                    ]],
                ],
            ],
            [
                'slug' => 'mingli-ask',
                'icon' => 'fa-yin-yang',
                'category' => 'destiny',
                'title_key' => 'app_mingli_ask_title',
                'desc_key' => 'app_mingli_ask_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 15,
                'enabled' => true,
                'badge' => 'popular',
                'search_keywords' => '八字,命理,紫微,玄學,合婚,事業運',
                'builtin_prompt' => 'You are a Chinese metaphysics (命理) consultant. Use BaZi / Zi Wei concepts carefully, explain reasoning, and give practical suggestions. Treat results as cultural reference, not fate. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'birth_datetime', 'type' => 'text', 'label_key' => 'field_birth_datetime', 'placeholder_key' => 'ph_birth_datetime', 'required' => true, 'maxlength' => 40],
                    ['name' => 'gender', 'type' => 'select', 'label_key' => 'field_gender', 'required' => true, 'options' => [
                        ['value' => 'female', 'label_key' => 'opt_female'],
                        ['value' => 'male', 'label_key' => 'opt_male'],
                        ['value' => 'neutral', 'label_key' => 'opt_neutral'],
                    ]],
                    ['name' => 'topic', 'type' => 'select', 'label_key' => 'field_mingli_topic', 'required' => true, 'options' => [
                        ['value' => 'career', 'label_key' => 'opt_focus_career'],
                        ['value' => 'love', 'label_key' => 'opt_focus_love'],
                        ['value' => 'wealth', 'label_key' => 'opt_focus_wealth'],
                        ['value' => 'marriage', 'label_key' => 'opt_mingli_marriage'],
                        ['value' => 'year', 'label_key' => 'opt_mingli_year'],
                        ['value' => 'general', 'label_key' => 'opt_focus_general'],
                    ]],
                    ['name' => 'year', 'type' => 'text', 'label_key' => 'field_mingli_year', 'placeholder_key' => 'ph_mingli_year', 'required' => false, 'maxlength' => 20],
                    ['name' => 'question', 'type' => 'textarea', 'label_key' => 'field_mingli_question', 'placeholder_key' => 'ph_mingli_question', 'required' => false, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'fitness-plan',
                'icon' => 'fa-dumbbell',
                'category' => 'health',
                'title_key' => 'app_fitness_plan_title',
                'desc_key' => 'app_fitness_plan_desc',
                'disclaimer_key' => 'disclaimer_health',
                'sort_order' => 80,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '健身,教練,訓練,體態,CrossFit',
                'builtin_prompt' => 'You are a practical fitness coach. Build a clear weekly plan, sets/reps guidance, recovery tips, and form cues. Flag when medical advice is needed. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'goal', 'type' => 'select', 'label_key' => 'field_fitness_goal', 'required' => true, 'options' => [
                        ['value' => 'fat_loss', 'label_key' => 'opt_fitness_fat'],
                        ['value' => 'muscle', 'label_key' => 'opt_fitness_muscle'],
                        ['value' => 'endurance', 'label_key' => 'opt_fitness_endurance'],
                        ['value' => 'general', 'label_key' => 'opt_fitness_general'],
                    ]],
                    ['name' => 'experience', 'type' => 'select', 'label_key' => 'field_fitness_exp', 'required' => true, 'options' => [
                        ['value' => 'beginner', 'label_key' => 'opt_exp_beginner'],
                        ['value' => 'intermediate', 'label_key' => 'opt_exp_mid'],
                        ['value' => 'advanced', 'label_key' => 'opt_exp_adv'],
                    ]],
                    ['name' => 'days', 'type' => 'select', 'label_key' => 'field_fitness_days', 'required' => true, 'options' => [
                        ['value' => '2', 'label_key' => 'opt_days_2'],
                        ['value' => '3', 'label_key' => 'opt_days_3'],
                        ['value' => '4', 'label_key' => 'opt_days_4'],
                        ['value' => '5', 'label_key' => 'opt_days_5'],
                    ]],
                    ['name' => 'equipment', 'type' => 'text', 'label_key' => 'field_fitness_equip', 'placeholder_key' => 'ph_fitness_equip', 'required' => false, 'maxlength' => 80],
                    ['name' => 'injuries', 'type' => 'textarea', 'label_key' => 'field_fitness_injuries', 'placeholder_key' => 'ph_fitness_injuries', 'required' => false, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'tarot-draw',
                'icon' => 'fa-moon',
                'category' => 'destiny',
                'title_key' => 'app_tarot_draw_title',
                'desc_key' => 'app_tarot_draw_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 55,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '塔羅,占卜,牌陣',
                'builtin_prompt' => 'You are a thoughtful tarot reader. Draw a symbolic spread, interpret cards clearly, and give reflective guidance without fatalism. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'topic', 'type' => 'select', 'label_key' => 'field_tarot_topic', 'required' => true, 'options' => [
                        ['value' => 'love', 'label_key' => 'opt_focus_love'],
                        ['value' => 'career', 'label_key' => 'opt_focus_career'],
                        ['value' => 'decision', 'label_key' => 'opt_tarot_decision'],
                        ['value' => 'general', 'label_key' => 'opt_focus_general'],
                    ]],
                    ['name' => 'spread', 'type' => 'select', 'label_key' => 'field_tarot_spread', 'required' => true, 'options' => [
                        ['value' => 'three', 'label_key' => 'opt_tarot_three'],
                        ['value' => 'one', 'label_key' => 'opt_tarot_one'],
                        ['value' => 'celtic', 'label_key' => 'opt_tarot_celtic'],
                    ]],
                    ['name' => 'question', 'type' => 'textarea', 'label_key' => 'field_tarot_question', 'placeholder_key' => 'ph_tarot_question', 'required' => true, 'maxlength' => 300],
                ],
            ],
            // --- Batch 2: inventory-backed themes (50-page research) + disclaimers ---
            [
                'slug' => 'therapy-checkin',
                'icon' => 'fa-comments',
                'category' => 'emotion',
                'title_key' => 'app_therapy_checkin_title',
                'desc_key' => 'app_therapy_checkin_desc',
                'disclaimer_key' => 'disclaimer_mental',
                'sort_order' => 45,
                'enabled' => true,
                'badge' => 'popular',
                'search_keywords' => '心理,諮詢,輔導,情緒,療癒',
                'builtin_prompt' => 'You are a supportive reflective listener. Help structure feelings and options. Never claim to be a licensed therapist, diagnose, or handle emergencies—direct users to local crisis services when needed. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'mood', 'type' => 'select', 'label_key' => 'field_mood', 'required' => true, 'options' => [
                        ['value' => '1', 'label_key' => 'opt_mood_1'],
                        ['value' => '3', 'label_key' => 'opt_mood_3'],
                        ['value' => '5', 'label_key' => 'opt_mood_5'],
                        ['value' => '7', 'label_key' => 'opt_mood_7'],
                        ['value' => '9', 'label_key' => 'opt_mood_9'],
                    ]],
                    ['name' => 'context', 'type' => 'select', 'label_key' => 'field_emotion_context', 'required' => true, 'options' => [
                        ['value' => 'work', 'label_key' => 'opt_ctx_work'],
                        ['value' => 'relationship', 'label_key' => 'opt_ctx_relationship'],
                        ['value' => 'family', 'label_key' => 'opt_ctx_family'],
                        ['value' => 'self', 'label_key' => 'opt_ctx_self'],
                        ['value' => 'other', 'label_key' => 'opt_legal_other'],
                    ]],
                    ['name' => 'what_happened', 'type' => 'textarea', 'label_key' => 'field_what_happened', 'placeholder_key' => 'ph_what_happened', 'required' => true, 'maxlength' => 400],
                    ['name' => 'want', 'type' => 'select', 'label_key' => 'field_want_help', 'required' => true, 'options' => [
                        ['value' => 'listen', 'label_key' => 'opt_want_listen'],
                        ['value' => 'advice', 'label_key' => 'opt_want_advice'],
                        ['value' => 'reframe', 'label_key' => 'opt_want_reframe'],
                    ]],
                ],
            ],
            [
                'slug' => 'invest-brief',
                'icon' => 'fa-chart-line',
                'category' => 'career',
                'title_key' => 'app_invest_brief_title',
                'desc_key' => 'app_invest_brief_desc',
                'disclaimer_key' => 'disclaimer_invest',
                'sort_order' => 75,
                'enabled' => true,
                'badge' => 'popular',
                'search_keywords' => '投資,基金,股票,資產配置,交易',
                'builtin_prompt' => 'You are an investment education assistant. Build frameworks and questions only—never personalized investment advice, price targets, or guarantees. Stress risk of loss. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'risk', 'type' => 'select', 'label_key' => 'field_invest_risk', 'required' => true, 'options' => [
                        ['value' => 'low', 'label_key' => 'opt_risk_low'],
                        ['value' => 'mid', 'label_key' => 'opt_risk_mid'],
                        ['value' => 'high', 'label_key' => 'opt_risk_high'],
                    ]],
                    ['name' => 'horizon', 'type' => 'select', 'label_key' => 'field_invest_horizon', 'required' => true, 'options' => [
                        ['value' => '1y', 'label_key' => 'opt_horizon_1y'],
                        ['value' => '3y', 'label_key' => 'opt_horizon_3y'],
                        ['value' => '5y', 'label_key' => 'opt_horizon_5y'],
                        ['value' => '10y', 'label_key' => 'opt_horizon_10y'],
                    ]],
                    ['name' => 'asset', 'type' => 'text', 'label_key' => 'field_invest_asset', 'placeholder_key' => 'ph_invest_asset', 'required' => true, 'maxlength' => 80],
                    ['name' => 'goal', 'type' => 'textarea', 'label_key' => 'field_invest_goal', 'placeholder_key' => 'ph_invest_goal', 'required' => true, 'maxlength' => 250],
                    ['name' => 'constraints', 'type' => 'text', 'label_key' => 'field_invest_constraints', 'placeholder_key' => 'ph_invest_constraints', 'required' => false, 'maxlength' => 100],
                ],
            ],
            [
                'slug' => 'brand-story',
                'icon' => 'fa-bullhorn',
                'category' => 'career',
                'title_key' => 'app_brand_story_title',
                'desc_key' => 'app_brand_story_desc',
                'disclaimer_key' => 'disclaimer_general',
                'sort_order' => 78,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '品牌,行銷,故事,定位,SEO',
                'builtin_prompt' => 'You are a brand strategist. Deliver positioning, narrative pillars, and channel-ready story angles. No guaranteed ROI. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'brand', 'type' => 'text', 'label_key' => 'field_brand_name', 'placeholder_key' => 'ph_brand_name', 'required' => true, 'maxlength' => 60],
                    ['name' => 'category', 'type' => 'text', 'label_key' => 'field_brand_category', 'placeholder_key' => 'ph_brand_category', 'required' => true, 'maxlength' => 60],
                    ['name' => 'audience', 'type' => 'text', 'label_key' => 'field_brand_audience', 'placeholder_key' => 'ph_brand_audience', 'required' => true, 'maxlength' => 100],
                    ['name' => 'diff', 'type' => 'textarea', 'label_key' => 'field_brand_diff', 'placeholder_key' => 'ph_brand_diff', 'required' => true, 'maxlength' => 250],
                    ['name' => 'tone', 'type' => 'text', 'label_key' => 'field_brand_tone', 'placeholder_key' => 'ph_brand_tone', 'required' => false, 'maxlength' => 60],
                ],
            ],
            [
                'slug' => 'leadership-sparring',
                'icon' => 'fa-users-cog',
                'category' => 'career',
                'title_key' => 'app_leadership_title',
                'desc_key' => 'app_leadership_desc',
                'disclaimer_key' => 'disclaimer_general',
                'sort_order' => 76,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '領導,管理,總監,策略,營運',
                'builtin_prompt' => 'You are an executive sparring partner. Role-play management scenarios with clear options, scripts, and risks. Not legal HR advice. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'role', 'type' => 'text', 'label_key' => 'field_leader_role', 'placeholder_key' => 'ph_leader_role', 'required' => true, 'maxlength' => 60],
                    ['name' => 'scenario', 'type' => 'select', 'label_key' => 'field_leader_scenario', 'required' => true, 'options' => [
                        ['value' => 'conflict', 'label_key' => 'opt_leader_conflict'],
                        ['value' => '1on1', 'label_key' => 'opt_leader_1on1'],
                        ['value' => 'perf', 'label_key' => 'opt_leader_perf'],
                        ['value' => 'change', 'label_key' => 'opt_leader_change'],
                        ['value' => 'other', 'label_key' => 'opt_legal_other'],
                    ]],
                    ['name' => 'counterpart', 'type' => 'text', 'label_key' => 'field_leader_who', 'placeholder_key' => 'ph_leader_who', 'required' => false, 'maxlength' => 80],
                    ['name' => 'goal', 'type' => 'textarea', 'label_key' => 'field_leader_goal', 'placeholder_key' => 'ph_leader_goal', 'required' => true, 'maxlength' => 250],
                ],
            ],
            [
                'slug' => 'medical-edu',
                'icon' => 'fa-user-md',
                'category' => 'health',
                'title_key' => 'app_medical_edu_title',
                'desc_key' => 'app_medical_edu_desc',
                'disclaimer_key' => 'disclaimer_medical',
                'sort_order' => 82,
                'enabled' => true,
                'badge' => 'popular',
                'search_keywords' => '醫師,醫生,臨床,護士,專科',
                'builtin_prompt' => 'You help patients prepare questions for clinicians. Never diagnose, prescribe, or claim medical authority. Urge emergency care when red flags appear. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'main_symptom', 'type' => 'text', 'label_key' => 'field_med_main', 'placeholder_key' => 'ph_med_main', 'required' => true, 'maxlength' => 100],
                    ['name' => 'duration', 'type' => 'text', 'label_key' => 'field_med_duration', 'placeholder_key' => 'ph_med_duration', 'required' => true, 'maxlength' => 40],
                    ['name' => 'tests', 'type' => 'textarea', 'label_key' => 'field_med_tests', 'placeholder_key' => 'ph_med_tests', 'required' => false, 'maxlength' => 200],
                    ['name' => 'meds', 'type' => 'text', 'label_key' => 'field_med_meds', 'placeholder_key' => 'ph_med_meds', 'required' => false, 'maxlength' => 100],
                    ['name' => 'questions', 'type' => 'textarea', 'label_key' => 'field_med_questions', 'placeholder_key' => 'ph_med_questions', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'tcm-herbal',
                'icon' => 'fa-leaf',
                'category' => 'health',
                'title_key' => 'app_tcm_herbal_title',
                'desc_key' => 'app_tcm_herbal_desc',
                'disclaimer_key' => 'disclaimer_tcm',
                'sort_order' => 81,
                'enabled' => true,
                'badge' => null,
                // Title match for souls: 中藥 / 中醫 / 本草 / TCM herbal etc.
                'search_keywords' => '中藥,中醫,本草,草藥,中藥材,藥膳,方劑,TCM,herbal,chinese medicine',
                'builtin_prompt' => "You are a Traditional Chinese Medicine (中醫／中藥) education guide. Explain concepts such as 寒熱虛實, 氣血津液, common herbs, classic formulas, and food-as-medicine (藥膳) at a cultural/educational level. Structure answers: (1) how TCM might frame the concern, (2) lifestyle / diet notes, (3) questions to ask a licensed TCM practitioner, (4) clear safety red flags. NEVER invent a clinical diagnosis, NEVER prescribe dosages as medical treatment, NEVER tell users to stop western medicine. Flag interactions, pregnancy, children, and emergency symptoms. Prefer plain language tables for herb/formula overviews. Respond in the user's language.",
                'fields' => [
                    ['name' => 'focus', 'type' => 'select', 'label_key' => 'field_tcm_focus', 'required' => true, 'options' => [
                        ['value' => 'herb_intro', 'label_key' => 'opt_tcm_herb_intro'],
                        ['value' => 'formula', 'label_key' => 'opt_tcm_formula'],
                        ['value' => 'constitution', 'label_key' => 'opt_tcm_constitution'],
                        ['value' => 'diet', 'label_key' => 'opt_tcm_diet'],
                        ['value' => 'seasonal', 'label_key' => 'opt_tcm_seasonal'],
                        ['value' => 'prep_visit', 'label_key' => 'opt_tcm_prep_visit'],
                    ]],
                    ['name' => 'concern', 'type' => 'textarea', 'label_key' => 'field_tcm_concern', 'placeholder_key' => 'ph_tcm_concern', 'required' => true, 'maxlength' => 300],
                    ['name' => 'body_notes', 'type' => 'textarea', 'label_key' => 'field_tcm_body', 'placeholder_key' => 'ph_tcm_body', 'required' => false, 'maxlength' => 250],
                    ['name' => 'current_use', 'type' => 'text', 'label_key' => 'field_tcm_current', 'placeholder_key' => 'ph_tcm_current', 'required' => false, 'maxlength' => 120],
                    ['name' => 'allergy_meds', 'type' => 'text', 'label_key' => 'field_tcm_allergy', 'placeholder_key' => 'ph_tcm_allergy', 'required' => false, 'maxlength' => 120],
                    ['name' => 'question', 'type' => 'textarea', 'label_key' => 'field_tcm_question', 'placeholder_key' => 'ph_tcm_question', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'parenting-guide',
                'icon' => 'fa-baby',
                'category' => 'life',
                'title_key' => 'app_parenting_title',
                'desc_key' => 'app_parenting_desc',
                'disclaimer_key' => 'disclaimer_medical',
                'sort_order' => 35,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '育兒,兒童,嬰,小兒,親子,產後',
                'builtin_prompt' => 'You are a parenting education guide. Give practical, age-aware tips. Not a pediatric diagnosis. Flag emergencies. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'child_age', 'type' => 'text', 'label_key' => 'field_child_age', 'placeholder_key' => 'ph_child_age', 'required' => true, 'maxlength' => 30],
                    ['name' => 'topic', 'type' => 'select', 'label_key' => 'field_parent_topic', 'required' => true, 'options' => [
                        ['value' => 'sleep', 'label_key' => 'opt_parent_sleep'],
                        ['value' => 'feed', 'label_key' => 'opt_parent_feed'],
                        ['value' => 'emotion', 'label_key' => 'opt_parent_emotion'],
                        ['value' => 'develop', 'label_key' => 'opt_parent_develop'],
                        ['value' => 'other', 'label_key' => 'opt_legal_other'],
                    ]],
                    ['name' => 'tried', 'type' => 'textarea', 'label_key' => 'field_parent_tried', 'placeholder_key' => 'ph_parent_tried', 'required' => false, 'maxlength' => 200],
                    ['name' => 'detail', 'type' => 'textarea', 'label_key' => 'field_parent_detail', 'placeholder_key' => 'ph_parent_detail', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'nutrition-plan',
                'icon' => 'fa-apple-alt',
                'category' => 'health',
                'title_key' => 'app_nutrition_title',
                'desc_key' => 'app_nutrition_desc',
                'disclaimer_key' => 'disclaimer_health',
                'sort_order' => 84,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '營養,飲食,食療,膳食',
                'builtin_prompt' => 'You draft educational meal-direction ideas only—not medical nutrition therapy. Respect allergies. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'goal', 'type' => 'select', 'label_key' => 'field_nutri_goal', 'required' => true, 'options' => [
                        ['value' => 'balance', 'label_key' => 'opt_nutri_balance'],
                        ['value' => 'loss', 'label_key' => 'opt_nutri_loss'],
                        ['value' => 'gain', 'label_key' => 'opt_nutri_gain'],
                        ['value' => 'energy', 'label_key' => 'opt_nutri_energy'],
                    ]],
                    ['name' => 'allergy', 'type' => 'text', 'label_key' => 'field_nutri_allergy', 'placeholder_key' => 'ph_nutri_allergy', 'required' => false, 'maxlength' => 100],
                    ['name' => 'meals', 'type' => 'select', 'label_key' => 'field_nutri_meals', 'required' => true, 'options' => [
                        ['value' => '3', 'label_key' => 'opt_meals_3'],
                        ['value' => '4', 'label_key' => 'opt_meals_4'],
                        ['value' => '5', 'label_key' => 'opt_meals_5'],
                    ]],
                    ['name' => 'activity', 'type' => 'text', 'label_key' => 'field_nutri_activity', 'placeholder_key' => 'ph_nutri_activity', 'required' => false, 'maxlength' => 60],
                    ['name' => 'notes', 'type' => 'textarea', 'label_key' => 'field_nutri_notes', 'placeholder_key' => 'ph_nutri_notes', 'required' => false, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'sleep-coach',
                'icon' => 'fa-bed',
                'category' => 'health',
                'title_key' => 'app_sleep_title',
                'desc_key' => 'app_sleep_desc',
                'disclaimer_key' => 'disclaimer_health',
                'sort_order' => 83,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '睡眠,安睡,失眠,作息',
                'builtin_prompt' => 'You coach sleep hygiene and routines. Not a sleep-medicine diagnosis. Suggest seeing a clinician for severe insomnia. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'age_group', 'type' => 'select', 'label_key' => 'field_sleep_age', 'required' => true, 'options' => [
                        ['value' => 'adult', 'label_key' => 'opt_sleep_adult'],
                        ['value' => 'teen', 'label_key' => 'opt_sleep_teen'],
                        ['value' => 'child', 'label_key' => 'opt_sleep_child'],
                        ['value' => 'infant', 'label_key' => 'opt_sleep_infant'],
                    ]],
                    ['name' => 'bedtime', 'type' => 'text', 'label_key' => 'field_sleep_bed', 'placeholder_key' => 'ph_sleep_bed', 'required' => true, 'maxlength' => 20],
                    ['name' => 'issue', 'type' => 'textarea', 'label_key' => 'field_sleep_issue', 'placeholder_key' => 'ph_sleep_issue', 'required' => true, 'maxlength' => 250],
                    ['name' => 'caffeine', 'type' => 'text', 'label_key' => 'field_sleep_caffeine', 'placeholder_key' => 'ph_sleep_caffeine', 'required' => false, 'maxlength' => 40],
                ],
            ],
            [
                'slug' => 'writing-coach',
                'icon' => 'fa-pen-nib',
                'category' => 'career',
                'title_key' => 'app_writing_title',
                'desc_key' => 'app_writing_desc',
                'disclaimer_key' => 'disclaimer_general',
                'sort_order' => 77,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '寫作,文案,作文,腳本,技術寫作',
                'builtin_prompt' => 'You are a writing coach. Improve structure, clarity, and voice. Encourage original work; do not help with academic dishonesty. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'genre', 'type' => 'select', 'label_key' => 'field_write_genre', 'required' => true, 'options' => [
                        ['value' => 'essay', 'label_key' => 'opt_write_essay'],
                        ['value' => 'copy', 'label_key' => 'opt_write_copy'],
                        ['value' => 'story', 'label_key' => 'opt_write_story'],
                        ['value' => 'tech', 'label_key' => 'opt_write_tech'],
                        ['value' => 'script', 'label_key' => 'opt_write_script'],
                    ]],
                    ['name' => 'audience', 'type' => 'text', 'label_key' => 'field_write_audience', 'placeholder_key' => 'ph_write_audience', 'required' => true, 'maxlength' => 80],
                    ['name' => 'draft', 'type' => 'textarea', 'label_key' => 'field_write_draft', 'placeholder_key' => 'ph_write_draft', 'required' => true, 'maxlength' => 500],
                    ['name' => 'goal', 'type' => 'text', 'label_key' => 'field_write_goal', 'placeholder_key' => 'ph_write_goal', 'required' => false, 'maxlength' => 80],
                ],
            ],
            [
                'slug' => 'travel-planner',
                'icon' => 'fa-plane',
                'category' => 'life',
                'title_key' => 'app_travel_title',
                'desc_key' => 'app_travel_desc',
                'disclaimer_key' => 'disclaimer_general',
                'sort_order' => 36,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '旅遊,行程,嚮導,朝聖',
                'builtin_prompt' => 'You design trip skeletons and experience ideas. Users must verify visas, safety, and insurance themselves. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'destination', 'type' => 'text', 'label_key' => 'field_travel_dest', 'placeholder_key' => 'ph_travel_dest', 'required' => true, 'maxlength' => 80],
                    ['name' => 'days', 'type' => 'text', 'label_key' => 'field_travel_days', 'placeholder_key' => 'ph_travel_days', 'required' => true, 'maxlength' => 20],
                    ['name' => 'budget', 'type' => 'select', 'label_key' => 'field_travel_budget', 'required' => true, 'options' => [
                        ['value' => 'budget', 'label_key' => 'opt_budget_low'],
                        ['value' => 'mid', 'label_key' => 'opt_budget_mid'],
                        ['value' => 'luxury', 'label_key' => 'opt_budget_high'],
                    ]],
                    ['name' => 'pace', 'type' => 'select', 'label_key' => 'field_travel_pace', 'required' => false, 'options' => [
                        ['value' => 'slow', 'label_key' => 'opt_pace_slow'],
                        ['value' => 'balanced', 'label_key' => 'opt_pace_mid'],
                        ['value' => 'packed', 'label_key' => 'opt_pace_fast'],
                    ]],
                    ['name' => 'interests', 'type' => 'textarea', 'label_key' => 'field_travel_interests', 'placeholder_key' => 'ph_travel_interests', 'required' => true, 'maxlength' => 200],
                ],
            ],
            [
                'slug' => 'pet-care',
                'icon' => 'fa-paw',
                'category' => 'life',
                'title_key' => 'app_pet_title',
                'desc_key' => 'app_pet_desc',
                'disclaimer_key' => 'disclaimer_vet',
                'sort_order' => 37,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '寵物,毛孩,寵物健康',
                'builtin_prompt' => 'You give pet care education and red-flag timing for vet visits. Never diagnose or prescribe. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'species', 'type' => 'select', 'label_key' => 'field_pet_species', 'required' => true, 'options' => [
                        ['value' => 'dog', 'label_key' => 'opt_pet_dog'],
                        ['value' => 'cat', 'label_key' => 'opt_pet_cat'],
                        ['value' => 'other', 'label_key' => 'opt_legal_other'],
                    ]],
                    ['name' => 'age', 'type' => 'text', 'label_key' => 'field_pet_age', 'placeholder_key' => 'ph_pet_age', 'required' => true, 'maxlength' => 30],
                    ['name' => 'issue', 'type' => 'textarea', 'label_key' => 'field_pet_issue', 'placeholder_key' => 'ph_pet_issue', 'required' => true, 'maxlength' => 300],
                ],
            ],
            [
                'slug' => 'zodiac-match',
                'icon' => 'fa-star-and-crescent',
                'category' => 'emotion',
                'title_key' => 'app_zodiac_title',
                'desc_key' => 'app_zodiac_desc',
                'disclaimer_key' => 'disclaimer_destiny',
                'sort_order' => 42,
                'enabled' => true,
                'badge' => null,
                'search_keywords' => '星座,占星',
                'builtin_prompt' => 'You discuss astrology as entertainment and relationship reflection—not destiny. Respond in the user\'s language.',
                'fields' => [
                    ['name' => 'sign_a', 'type' => 'text', 'label_key' => 'field_sign_a', 'placeholder_key' => 'ph_sign', 'required' => true, 'maxlength' => 20],
                    ['name' => 'sign_b', 'type' => 'text', 'label_key' => 'field_sign_b', 'placeholder_key' => 'ph_sign', 'required' => false, 'maxlength' => 20],
                    ['name' => 'relation', 'type' => 'select', 'label_key' => 'field_zodiac_rel', 'required' => true, 'options' => [
                        ['value' => 'romance', 'label_key' => 'opt_zodiac_romance'],
                        ['value' => 'friend', 'label_key' => 'opt_zodiac_friend'],
                        ['value' => 'work', 'label_key' => 'opt_zodiac_work'],
                        ['value' => 'self', 'label_key' => 'opt_zodiac_self'],
                    ]],
                    ['name' => 'question', 'type' => 'textarea', 'label_key' => 'field_zodiac_q', 'placeholder_key' => 'ph_zodiac_q', 'required' => true, 'maxlength' => 250],
                ],
            ],
        ];
    }

    /**
     * Theme keywords for soul search (space-separated). Any keyword may match (OR).
     */
    public static function searchKeywordsForApp(array $app): string
    {
        $kw = trim((string)($app['search_keywords'] ?? ''));
        return $kw !== '' ? $kw : (string)($app['slug'] ?? '');
    }

    /**
     * Parse theme keywords. Delimiter is comma or | only — never bare spaces —
     * so English phrases like "feng shui" / "name advisor" stay intact.
     *
     * @return list<string>
     */
    public static function parseKeywords(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        // Prefer explicit separators; if none, treat whole string as one phrase
        if (strpbrk($q, ',|') === false) {
            $parts = [$q];
        } else {
            $parts = preg_split('/\s*[,|]\s*/u', $q, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($parts)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p === '') {
                continue;
            }
            $key = mb_strtolower($p, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $p;
            if (count($out) >= 12) {
                break;
            }
        }
        return $out;
    }

    /**
     * Search public Web2 souls by theme keywords (OR across keywords; mirrors browse is_nft=0).
     *
     * @return list<array<string, mixed>>
     */
    public static function searchPublicSouls(PDO $pdo, string $keywords, int $limit = 24): array
    {
        $limit = max(1, min(50, $limit));
        $kws = self::parseKeywords($keywords);

        $where = " WHERE s.is_public = 1 AND (s.is_nft = 0 OR s.is_nft IS NULL)";
        $binds = [];

        if ($kws !== []) {
            $orParts = [];
            foreach ($kws as $kw) {
                $like = '%' . $kw . '%';
                // Title only — avoid noisy description/domain matches
                $orParts[] = 's.title LIKE ?';
                $binds[] = $like;
            }
            $where .= ' AND (' . implode(' OR ', $orParts) . ')';
        }

        $sql = "SELECT s.id, s.title, s.description, s.role, s.domain, s.compatibility,
                       s.file_type, s.like_count, s.fork_count, s.created_at,
                       u.username, c.name AS role_name, c.icon AS role_icon
                FROM souls s
                LEFT JOIN users u ON u.id = s.user_id
                LEFT JOIN categories c ON s.role = c.slug
                {$where}
                ORDER BY s.like_count DESC, s.created_at DESC
                LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($binds as $b) {
            $stmt->bindValue($i++, $b, PDO::PARAM_STR);
        }
        $stmt->bindValue($i, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $row) {
            $domains = array_values(array_filter(array_map('trim', explode(',', (string)($row['domain'] ?? '')))));
            $out[] = [
                'id' => (int)$row['id'],
                'title' => (string)($row['title'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'role' => (string)($row['role'] ?? ''),
                'role_name' => (string)($row['role_name'] ?? $row['role'] ?? ''),
                'role_icon' => (string)($row['role_icon'] ?? ''),
                'domain' => (string)($row['domain'] ?? ''),
                'domains' => array_slice($domains, 0, 4),
                'compatibility' => (string)($row['compatibility'] ?? ''),
                'file_type' => (string)($row['file_type'] ?? ''),
                'like_count' => (int)($row['like_count'] ?? 0),
                'fork_count' => (int)($row['fork_count'] ?? 0),
                'username' => (string)($row['username'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Whether soul_id is a public non-NFT soul matching the app theme keywords.
     */
    public static function isSoulAllowed(PDO $pdo, string $slug, int $soulId): bool
    {
        if ($soulId <= 0) {
            return false;
        }
        $app = self::getBySlug($slug);
        if (!$app) {
            return false;
        }

        $kws = self::parseKeywords(self::searchKeywordsForApp($app));
        $sql = "SELECT s.id FROM souls s
                WHERE s.id = ?
                  AND s.is_public = 1
                  AND (s.is_nft = 0 OR s.is_nft IS NULL)";
        $binds = [$soulId];
        if ($kws !== []) {
            $orParts = [];
            foreach ($kws as $kw) {
                $like = '%' . $kw . '%';
                $orParts[] = 's.title LIKE ?';
                $binds[] = $like;
            }
            $sql .= ' AND (' . implode(' OR ', $orParts) . ')';
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($binds);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listPublic(?string $category = null, ?string $q = null): array
    {
        $out = [];
        foreach (self::allRaw() as $app) {
            if (empty($app['enabled'])) {
                continue;
            }
            if ($category !== null && $category !== '' && ($app['category'] ?? '') !== $category) {
                continue;
            }
            $title = function_exists('__') ? __($app['title_key']) : $app['title_key'];
            $desc = function_exists('__') ? __($app['desc_key']) : $app['desc_key'];
            if ($q !== null && $q !== '') {
                $hay = mb_strtolower($title . ' ' . $desc . ' ' . $app['slug'], 'UTF-8');
                if (mb_strpos($hay, mb_strtolower($q, 'UTF-8')) === false) {
                    continue;
                }
            }
            $out[] = [
                'slug' => $app['slug'],
                'icon' => $app['icon'],
                'category' => $app['category'],
                'title' => $title,
                'description' => $desc,
                'badge' => $app['badge'] ?? null,
                'field_count' => count($app['fields'] ?? []),
                'search_keywords' => self::searchKeywordsForApp($app),
                'sort_order' => (int)($app['sort_order'] ?? 0),
            ];
        }
        usort($out, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getBySlug(string $slug): ?array
    {
        foreach (self::allRaw() as $app) {
            if (($app['slug'] ?? '') === $slug && !empty($app['enabled'])) {
                return $app;
            }
        }
        return null;
    }

    /**
     * Public detail with localized labels + souls found by theme keyword search.
     *
     * @return array<string, mixed>|null
     */
    public static function getPublicDetail(string $slug, ?PDO $pdo = null): ?array
    {
        $app = self::getBySlug($slug);
        if (!$app) {
            return null;
        }

        $keywords = self::searchKeywordsForApp($app);
        $souls = [];
        if ($pdo instanceof PDO) {
            $souls = self::searchPublicSouls($pdo, $keywords, 24);
        }

        $fields = [];
        foreach ($app['fields'] as $f) {
            $field = [
                'name' => $f['name'],
                'type' => $f['type'],
                'label' => function_exists('__') ? __($f['label_key']) : $f['label_key'],
                'required' => !empty($f['required']),
            ];
            if (!empty($f['placeholder_key'])) {
                $field['placeholder'] = function_exists('__') ? __($f['placeholder_key']) : $f['placeholder_key'];
            }
            if (isset($f['maxlength'])) {
                $field['maxlength'] = (int)$f['maxlength'];
            }
            if (!empty($f['options']) && is_array($f['options'])) {
                $field['options'] = array_map(function ($opt) {
                    return [
                        'value' => $opt['value'],
                        'label' => function_exists('__') ? __($opt['label_key']) : $opt['label_key'],
                    ];
                }, $f['options']);
            }
            $fields[] = $field;
        }

        $disclaimer = null;
        if (!empty($app['disclaimer_key']) && function_exists('__')) {
            $disclaimer = __($app['disclaimer_key']);
        }

        return [
            'slug' => $app['slug'],
            'icon' => $app['icon'],
            'category' => $app['category'],
            'title' => function_exists('__') ? __($app['title_key']) : $app['title_key'],
            'description' => function_exists('__') ? __($app['desc_key']) : $app['desc_key'],
            'badge' => $app['badge'] ?? null,
            'disclaimer' => $disclaimer,
            'fields' => $fields,
            'search_keywords' => $keywords,
            'souls' => $souls,
            'soul_count' => count($souls),
        ];
    }

    /**
     * Validate submitted fields against schema. Returns [ok, errorMessage, sanitizedValues].
     *
     * @param array<string, mixed> $input
     * @return array{0:bool,1:?string,2:array<string,string>}
     */
    public static function validateFields(array $app, array $input): array
    {
        $sanitized = [];
        foreach ($app['fields'] as $f) {
            $name = $f['name'];
            $raw = $input[$name] ?? '';
            if (is_array($raw)) {
                return [false, function_exists('__') ? __('Invalid app field value') : 'Invalid app field value', []];
            }
            $val = trim((string)$raw);
            if (!empty($f['required']) && $val === '') {
                $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
                $msg = function_exists('__')
                    ? __('Missing required app field', ['field' => $label])
                    : "Missing required field: {$label}";
                return [false, $msg, []];
            }
            if ($val === '') {
                continue;
            }
            $max = isset($f['maxlength']) ? (int)$f['maxlength'] : 500;
            if (mb_strlen($val, 'UTF-8') > $max) {
                $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
                $msg = function_exists('__')
                    ? __('App field too long', ['field' => $label, 'max' => $max])
                    : "Field too long: {$label}";
                return [false, $msg, []];
            }
            if (($f['type'] ?? '') === 'select' && !empty($f['options'])) {
                $allowed = array_column($f['options'], 'value');
                if (!in_array($val, $allowed, true)) {
                    return [false, function_exists('__') ? __('Invalid app field value') : 'Invalid app field value', []];
                }
            }
            $sanitized[$name] = $val;
        }
        return [true, null, $sanitized];
    }

    /**
     * Build labeled user message from sanitized fields.
     *
     * @param array<string, string> $values
     */
    public static function formatUserMessage(array $app, array $values): string
    {
        $lines = [];
        // Prepend disclaimer so chat personas see liability boundary even if UI was skipped
        if (!empty($app['disclaimer_key']) && function_exists('__')) {
            $disc = trim((string)__($app['disclaimer_key']));
            if ($disc !== '') {
                $lines[] = '[' . (function_exists('__') ? __('Disclaimer label') : 'Disclaimer') . '] ' . $disc;
            }
        }
        foreach ($app['fields'] as $f) {
            $name = $f['name'];
            if (!array_key_exists($name, $values)) {
                continue;
            }
            $label = function_exists('__') ? __($f['label_key']) : $f['label_key'];
            $display = $values[$name];
            if (($f['type'] ?? '') === 'select' && !empty($f['options'])) {
                foreach ($f['options'] as $opt) {
                    if ($opt['value'] === $display) {
                        $display = function_exists('__') ? __($opt['label_key']) : $opt['label_key'];
                        break;
                    }
                }
            }
            $lines[] = "{$label}: {$display}";
        }
        return implode("\n", $lines);
    }

    public static function buildSystemPrompt(array $app, ?array $soul, array $tierConfig): string
    {
        $systemPrompt = '';
        if ($soul) {
            if (($soul['file_type'] ?? '') === 'full_soul_folder') {
                $systemPrompt .= "Please adopt the following modular AI persona:\n\n";
                $files = json_decode(str_replace("\\'", "'", $soul['content'] ?? ''), true);
                if (is_array($files)) {
                    foreach ($files as $filename => $fileContent) {
                        if (strpos((string)$filename, 'ERROR.md') !== false) {
                            continue;
                        }
                        $systemPrompt .= "=== MODULE: {$filename} ===\n"
                            . (is_string($fileContent) ? $fileContent : json_encode($fileContent, JSON_UNESCAPED_UNICODE))
                            . "\n\n";
                    }
                }
            } else {
                $systemPrompt = (string)($soul['content'] ?? '');
            }
        } elseif (!empty($app['builtin_prompt'])) {
            $systemPrompt = (string)$app['builtin_prompt'];
        }

        // Align length pressure with tier max_tokens (e.g. free 500) — same idea as chat word cap
        $maxWords = max(40, floor(($tierConfig['max_tokens'] ?? 500) * 0.55));
        $systemPrompt .= "\n\n[MINI APP MODE] Structured form answer. Be concise and fit within ~{$maxWords} words. Prefer a short table over long prose. Stop cleanly when the answer is complete.";
        return $systemPrompt;
    }
}
