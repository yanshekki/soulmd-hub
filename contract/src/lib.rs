use near_sdk::borsh::{self, BorshDeserialize, BorshSerialize};
use near_sdk::collections::LookupMap;
use near_sdk::json_types::U128;
use near_sdk::serde::{Deserialize, Serialize};
use near_sdk::{
    env, near, AccountId, Gas, NearToken, PanicOnDefault, Promise, PromiseOrValue,
};
use std::collections::HashMap;

#[derive(BorshDeserialize, BorshSerialize, Serialize, Deserialize, Clone)]
#[serde(crate = "near_sdk::serde")]
pub struct TokenMetadata {
    pub title: String,
    pub description: String,
    pub extra: String, // hash
    pub reference: String,
    pub creator_id: AccountId,
}

#[derive(BorshDeserialize, BorshSerialize, Serialize, Deserialize, Clone)]
#[serde(crate = "near_sdk::serde")]
pub struct Token {
    pub owner_id: AccountId,
    pub metadata: TokenMetadata,
    pub sale_price: Option<U128>,
    pub rent_price: Option<U128>,
    pub renters: HashMap<AccountId, u64>, // expiry ns
}

#[near(contract_state)]
#[derive(PanicOnDefault)]
pub struct SoulMDAgentFi {
    tokens: LookupMap<String, Token>, // key = "t:" + token_id
    platform_wallet: AccountId,
    usdt: AccountId,
    usdc: AccountId,
    rent_duration_ns: u64,
    credits: LookupMap<String, String>, // "account:tier" -> ts string
}

#[near]
impl SoulMDAgentFi {
    #[init]
    pub fn new() -> Self {
        Self {
            tokens: LookupMap::new(b"t".to_vec()),
            platform_wallet: "soulmd-hub.near".parse().unwrap(),
            usdt: "usdt.tether-token.near".parse().unwrap(),
            usdc: "17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1"
                .parse()
                .unwrap(),
            rent_duration_ns: 2592000000000000,
            credits: LookupMap::new(b"uc".to_vec()),
        }
    }

    // Mint
    #[payable]
    pub fn mint_soul(
        &mut self,
        token_id: String,
        title: String,
        description: String,
        hash: String,
        reference: String,
    ) {
        let caller = env::predecessor_account_id();
        let deposit = env::attached_deposit().as_yoctonear();

        let required: u128 = 600000000000000000000000; // 0.6 NEAR
        assert!(deposit >= required, "Error: Minting requires exactly 0.6 NEAR");

        let key = format!("t:{}", token_id);
        assert!(self.tokens.get(&key).is_none(), "Error: Token ID already exists.");

        let metadata = TokenMetadata {
            title,
            description,
            extra: hash,
            reference,
            creator_id: caller.clone(),
        };
        let token = Token {
            owner_id: caller.clone(),
            metadata,
            sale_price: None,
            rent_price: None,
            renters: HashMap::new(),
        };
        self.tokens.insert(&key, &token);

        let platform_fee: u128 = 100000000000000000000000; // 0.1 NEAR
        Promise::new(self.platform_wallet.clone()).transfer(NearToken::from_yoctonear(platform_fee)).detach();

        env::log_str(&format!("Minted Soul [{}] by {}", token_id, caller));
    }

    pub fn update_soul_hash(&mut self, token_id: String, new_hash: String) {
        let caller = env::predecessor_account_id();
        let key = format!("t:{}", token_id);
        let mut token = self.tokens.get(&key).expect("Error: Token not found.");
        assert!(token.owner_id == caller, "Security Error: Only the current owner can update hash.");

        token.metadata.extra = new_hash;
        self.tokens.insert(&key, &token);

        env::log_str(&format!("Soul [{}] evolved to new hash: {}", token_id, token.metadata.extra));
    }

    pub fn list_for_sale(&mut self, token_id: String, price: U128) {
        let caller = env::predecessor_account_id();
        let key = format!("t:{}", token_id);
        let mut token = self.tokens.get(&key).expect("Error: Token not found.");
        assert!(token.owner_id == caller, "Error: Only owner can list for sale.");

        if price.0 == 0 {
            token.sale_price = None;
            env::log_str(&format!("[{}] sale listing cancelled.", token_id));
        } else {
            token.sale_price = Some(price);
            env::log_str(&format!("[{}] listed for sale at {}", token_id, price.0));
        }
        self.tokens.insert(&key, &token);
    }

    #[payable]
    pub fn buy_soul(&mut self, token_id: String) {
        let buyer = env::predecessor_account_id();
        let deposit = env::attached_deposit().as_yoctonear();
        let key = format!("t:{}", token_id);
        let mut token = self.tokens.get(&key).expect("Error: Token not found.");

        let sale_price = token.sale_price.expect("Error: Token not listed for sale.");
        assert!(deposit >= sale_price.0, "Error: Insufficient deposit.");

        let prev_owner = token.owner_id.clone();
        let creator = token.metadata.creator_id.clone();

        let platform_fee = sale_price.0 * 5 / 100;
        let mut creator_value: u128 = 0;
        let mut seller_value = sale_price.0 - platform_fee;

        if creator != prev_owner {
            creator_value = sale_price.0 * 5 / 100;
            seller_value -= creator_value;
        }

        // State mutation BEFORE effects (payments). Per strict audit + prior TS safety pattern.
        token.owner_id = buyer.clone();
        token.sale_price = None;
        token.rent_price = None;
        self.tokens.insert(&key, &token);

        // Now effects (transfers). Platform 5%, optional creator 5%, seller rest.
        Promise::new(self.platform_wallet.clone()).transfer(NearToken::from_yoctonear(platform_fee)).detach();
        if creator_value > 0 {
            Promise::new(creator.clone()).transfer(NearToken::from_yoctonear(creator_value)).detach();
        }
        Promise::new(prev_owner.clone()).transfer(NearToken::from_yoctonear(seller_value)).detach();

        env::log_str(&format!("[{}] bought by {} from {}", token_id, buyer, prev_owner));
    }

    pub fn list_for_rent(&mut self, token_id: String, price: U128) {
        let caller = env::predecessor_account_id();
        let key = format!("t:{}", token_id);
        let mut token = self.tokens.get(&key).expect("Error: Token not found.");
        assert!(token.owner_id == caller, "Error: Only owner can list for rent.");

        if price.0 == 0 {
            token.rent_price = None;
            env::log_str(&format!("[{}] rent listing cancelled.", token_id));
        } else {
            token.rent_price = Some(price);
            env::log_str(&format!("[{}] listed for rent at {} yoctoNEAR / 30 Days", token_id, price.0));
        }
        self.tokens.insert(&key, &token);
    }

    #[payable]
    pub fn rent_soul(&mut self, token_id: String) {
        let renter = env::predecessor_account_id();
        let deposit = env::attached_deposit().as_yoctonear();
        let key = format!("t:{}", token_id);
        let mut token = self.tokens.get(&key).expect("Error: Token not found.");

        let rent_price = token.rent_price.expect("Error: Token not listed for rent.");
        assert!(deposit >= rent_price.0, "Error: Insufficient deposit for rent.");

        let platform_fee = rent_price.0 * 10 / 100;
        let owner_share = rent_price.0 - platform_fee;

        let now = env::block_timestamp();

        // clean expired
        let mut to_delete: Vec<AccountId> = vec![];
        for (r, exp) in token.renters.iter() {
            if *exp < now {
                to_delete.push(r.clone());
            }
        }
        for r in to_delete {
            token.renters.remove(&r);
        }

        let mut current_expiry = *token.renters.get(&renter).unwrap_or(&now);
        if current_expiry < now {
            current_expiry = now;
        }
        token.renters.insert(renter.clone(), current_expiry + self.rent_duration_ns);

        // State mutation BEFORE effects (payments). Per strict audit + prior TS safety pattern.
        self.tokens.insert(&key, &token);

        // Now effects (transfers): 10% platform, 90% to current owner.
        Promise::new(self.platform_wallet.clone()).transfer(NearToken::from_yoctonear(platform_fee)).detach();
        Promise::new(token.owner_id.clone()).transfer(NearToken::from_yoctonear(owner_share)).detach();

        env::log_str(&format!("Soul [{}] rented by {} (expiry {})", token_id, renter, token.renters.get(&renter).unwrap()));
    }

    pub fn burn_soul(&mut self, token_id: String) {
        let caller = env::predecessor_account_id();
        let key = format!("t:{}", token_id);
        let token = self.tokens.get(&key).expect("Error: Token not found.");
        assert!(token.owner_id == caller, "Error: Only owner can burn.");

        let now = env::block_timestamp();
        for (_r, exp) in token.renters.iter() {
            assert!(*exp < now, "Error: Cannot burn while active renters exist.");
        }

        self.tokens.remove(&key);

        let refund_amount: u128 = 450000000000000000000000;
        let platform_burn_fee: u128 = 50000000000000000000000;

        Promise::new(caller.clone()).transfer(NearToken::from_yoctonear(refund_amount)).detach();
        Promise::new(self.platform_wallet.clone()).transfer(NearToken::from_yoctonear(platform_burn_fee)).detach();

        env::log_str(&format!("Soul [{}] burned by {}", token_id, caller));
    }

    pub fn get_soul(&self, token_id: String) -> Option<Token> {
        self.tokens.get(&format!("t:{}", token_id))
    }

    pub fn check_access(&self, token_id: String, account_id: AccountId) -> bool {
        if let Some(token) = self.tokens.get(&format!("t:{}", token_id)) {
            if token.owner_id == account_id {
                return true;
            }
            if let Some(exp) = token.renters.get(&account_id) {
                if *exp > env::block_timestamp() {
                    return true;
                }
            }
        }
        false
    }

    #[payable]
    pub fn auto_buyback_and_burn(&mut self, amount_in_near: U128) {
        let caller = env::predecessor_account_id();
        assert!(caller == self.platform_wallet, "Security Error: Only platform treasury can trigger buyback.");

        let amount = amount_in_near.0;
        let attached = env::attached_deposit().as_yoctonear();
        assert!(attached >= amount, "Error: attach exactly the NEAR amount to use for buyback");

        // Simplified, in real would wrap and swap, but to match logic
        Promise::new("wrap.near".parse().unwrap())
            .function_call("near_deposit".to_string(), vec![], NearToken::from_yoctonear(amount), Gas::from_tgas(30)).detach();

        // For full, would do the ref finance swap, but for brevity keep similar.
        env::log_str(&format!("Auto-Buyback triggered for {}", amount_in_near.0));
    }

    // FT for USDT/USDC credits (raw storage "uc:acct:tier")
    pub fn ft_on_transfer(
        &mut self,
        sender_id: AccountId,
        amount: U128,
        msg: String,
    ) -> PromiseOrValue<U128> {
        let token = env::predecessor_account_id();
        if token != self.usdt && token != self.usdc {
            return PromiseOrValue::Value(amount);
        }

        let tier = if msg.to_lowercase().contains("vip") || msg.to_lowercase().contains("standard") {
            "vip"
        } else if msg.to_lowercase().contains("pro") || msg.to_lowercase().contains("advanced") {
            "pro"
        } else {
            ""
        };

        if tier.is_empty() {
            return PromiseOrValue::Value(amount);
        }

        let required = if tier == "vip" { 4_990_000u128 } else { 14_990_000u128 };
        if amount.0 < required {
            return PromiseOrValue::Value(amount);
        }

        let key = format!("{}:{}", sender_id, tier);
        let ts = env::block_timestamp().to_string();
        self.credits.insert(&key, &ts);

        env::log_str(&format!("FT credit granted: {} {}", sender_id, tier));
        PromiseOrValue::Value(U128(0))
    }

    pub fn has_upgrade_credit(&self, account_id: AccountId, tier: String) -> String {
        let key = format!("{}:{}", account_id, tier);
        if let Some(ts) = self.credits.get(&key) {
            let ts_val: u64 = ts.parse().unwrap_or(0);
            if env::block_timestamp() - ts_val > 86_400_000_000_000 {
                "0".to_string()
            } else {
                ts
            }
        } else {
            "0".to_string()
        }
    }

    // Practical admin god-mode for soulmd-hub.near
    pub fn admin_set_token(&mut self, token_id: String, owner_id: AccountId, title: String, description: String, hash: String, reference: String, sale_price: Option<U128>, rent_price: Option<U128>) {
        self.assert_platform();
        let key = format!("t:{}", token_id);
        let metadata = TokenMetadata {
            title,
            description,
            extra: hash,
            reference,
            creator_id: owner_id.clone(),
        };
        let token = Token {
            owner_id,
            metadata,
            sale_price,
            rent_price,
            renters: HashMap::new(),
        };
        self.tokens.insert(&key, &token);
        env::log_str(&format!("ADMIN set [{}]", token_id));
    }

    pub fn admin_remove_token(&mut self, token_id: String) {
        self.assert_platform();
        let key = format!("t:{}", token_id);
        self.tokens.remove(&key);
        env::log_str(&format!("ADMIN remove [{}]", token_id));
    }

    fn assert_platform(&self) {
        let caller = env::predecessor_account_id();
        assert!(caller == self.platform_wallet, "Security Error: Only platform_wallet (soulmd-hub.near) can call admin.");
    }
}
