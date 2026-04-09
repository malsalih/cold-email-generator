from flask import Flask, request, jsonify
import joblib
import re
import numpy as np

app = Flask(__name__)

try:
    vectorizer = joblib.load('vectorizer.pkl')
    model = joblib.load('spam_model.pkl')
    print("--- ML Spam Model API Booted Successfully ---")

    # Pre-compute: get the vocabulary and feature log-probabilities
    # This lets us identify WHICH words drive the spam classification
    feature_names = vectorizer.get_feature_names_out()
    # Higher value = more associated with spam
    spam_scores = model.feature_log_prob_[1] - model.feature_log_prob_[0]
    # Build a dict: word -> spam_contribution_score
    word_spam_scores = dict(zip(feature_names, spam_scores))

    print(f"Loaded vocabulary of {len(feature_names)} features for smart correction.")
except Exception as e:
    print(f"Error loading models: {e}")
    word_spam_scores = {}

# ──────────────────────────────────────────────
# GMAIL-AWARE + ML-DRIVEN CORRECTION ENGINE
# ──────────────────────────────────────────────

# Static keyword replacements (Gmail RETVec layer)
SPAM_REPLACEMENTS = [
    (re.compile(r'\bact now\b', re.I), 'let me know'),
    (re.compile(r'\bbuy now\b', re.I), 'discuss further'),
    (re.compile(r'\border now\b', re.I), 'reach out'),
    (re.compile(r'\bclick here\b', re.I), 'check this out'),
    (re.compile(r'\bguaranteed\b', re.I), 'well-positioned'),
    (re.compile(r'\blimited time\b', re.I), 'currently available'),
    (re.compile(r'\blowest price\b', re.I), 'competitive value'),
    (re.compile(r'\brisk[ -]free\b', re.I), 'straightforward'),
    (re.compile(r'\bspecial promotion\b', re.I), 'opportunity'),
    (re.compile(r'\bspecial offer\b', re.I), 'opportunity'),
    (re.compile(r'\bexclusive deal\b', re.I), 'opportunity'),
    (re.compile(r'\burgent(ly)?\b', re.I), 'timely'),
    (re.compile(r'\bwinner\b', re.I), 'candidate'),
    (re.compile(r'\bprize\b', re.I), 'asset'),
    (re.compile(r'\bcongratulations\b', re.I), ''),
    (re.compile(r'\bno obligation\b', re.I), 'no commitment needed'),
    (re.compile(r'\bdon\'t miss\b', re.I), 'consider'),
    (re.compile(r'\bonce in a lifetime\b', re.I), 'rare'),
    (re.compile(r'\bmake money\b', re.I), 'grow revenue'),
    (re.compile(r'\bearn money\b', re.I), 'grow revenue'),
    (re.compile(r'\bdouble your\b', re.I), 'improve your'),
    (re.compile(r'\b100%\b'), 'significantly'),
    (re.compile(r'\bfree\b', re.I), 'complimentary'),
    (re.compile(r'\bno cost\b', re.I), 'included'),
    (re.compile(r'\bsign up\b', re.I), 'connect'),
    (re.compile(r'\bcall now\b', re.I), 'reach out'),
    (re.compile(r'\bapply now\b', re.I), 'get in touch'),
    (re.compile(r'\bas seen on\b', re.I), ''),
    (re.compile(r'\bmiracle\b', re.I), 'powerful'),
    (re.compile(r'\brevolutionary\b', re.I), 'innovative'),
    (re.compile(r'\bdiscount\b', re.I), 'value'),
    (re.compile(r'\boffer expires\b', re.I), ''),
    (re.compile(r'\bdear sir/?madam\b', re.I), 'Hi there'),
    (re.compile(r'\bdear friend\b', re.I), 'Hi'),
    (re.compile(r'\$\$\$', re.I), ''),
]

# Safe replacements for ML-identified spam words
# These are neutral, human-sounding alternatives that preserve grammar
ML_SAFE_REPLACEMENTS = {
    # Offers / Deals
    'offer': 'option',
    'offers': 'options',
    'deal': 'arrangement',
    'deals': 'arrangements',
    'promotion': 'update',
    'discount': 'value',
    'cheap': 'accessible',
    'lowest': 'competitive',
    'affordable': 'reasonable',
    
    # Financial / Transactions
    'purchase': 'explore',
    'buy': 'consider',
    'sale': 'availability',
    'sales': 'results',
    'profit': 'growth',
    'profits': 'growth',
    'income': 'growth',
    'earn': 'achieve',
    'cash': 'value',
    'money': 'value',
    'invest': 'consider',
    'investment': 'opportunity',
    'save': 'benefit from',
    'saving': 'benefiting from',
    'price': 'valuation',
    'cost': 'budget',
    'fee': 'budget',
    
    # Action verbs
    'click': 'check',
    'subscribe': 'connect',
    'unsubscribe': '',
    'claim': 'explore',
    'verify': 'confirm',
    'confirm': 'share',
    'order': 'inquiry',
    'sign': 'connect',
    'register': 'connect',
    'join': 'connect',
    'guarantee': 'assure',
    'guaranteed': 'well-positioned',
    
    # Urgency / Scarcity
    'instantly': 'promptly',
    'immediately': 'promptly',
    'now': 'soon',
    'today': 'this week',
    'hurry': 'reach out',
    'limited': 'current',
    'urgent': 'timely',
    
    # Exaggerations
    'amazing': 'strong',
    'incredible': 'notable',
    'fantastic': 'solid',
    'tremendous': 'significant',
    'massive': 'substantial',
    'exclusive': 'specific',
    'best': 'ideal',
    'top': 'leading',
    'perfect': 'suitable',
    'revolutionary': 'innovative',
    'secret': 'strategy',
    
    # Status / Rewards
    'bonus': 'benefit',
    'reward': 'benefit',
    'prize': 'asset',
    'winner': 'candidate',
    'selected': 'considered',
    'chosen': 'considered',
    'eligible': 'positioned',
    'qualify': 'fit',
    'win': 'gain',
    'winning': 'gaining',
    'won': 'gained',
    
    # Noun replacers for spam-heavy generic words
    'traffic': 'visibility',
    'visitors': 'audience',
    'clicks': 'engagement',
    'views': 'engagement',
    'leads': 'connections',
}


def apply_keyword_filter(text):
    """Layer 1: Replace known spam trigger phrases."""
    for pattern, replacement in SPAM_REPLACEMENTS:
        text = pattern.sub(replacement, text)
    return text


def apply_ml_driven_correction(text, max_removals=6):
    """
    Layer 2 (ML-DRIVEN): Use the model's own feature weights
    to identify which specific words in THIS email contribute most
    to its spam classification, then replace ONLY if we have a safe
    alternative. Essential business words are protected.
    """
    if not word_spam_scores:
        return text

    # Words that are critical for domain sales emails — never remove these
    PROTECTED_WORDS = {
        'domain', 'brand', 'company', 'business', 'website', 'online',
        'digital', 'premium', 'acquire', 'acquiring', 'acquisition',
        'presence', 'authority', 'strategy', 'strategic', 'asset',
        'opportunity', 'interest', 'interested', 'conversation', 'chat',
        'discuss', 'discussing', 'best', 'regards', 'thanks', 'thank',
        'hello', 'name', 'team', 'growth', 'value', 'market',
        'marketing', 'currently', 'noticed', 'reaching', 'touch',
        'equity', 'strengthen', 'owner', 'represent', 'operating',
    }

    words_in_text = re.findall(r'\b[a-z]+\b', text.lower())
    word_contributions = []
    for w in set(words_in_text):
        if w in word_spam_scores and len(w) > 2 and w not in PROTECTED_WORDS:
            word_contributions.append((w, word_spam_scores[w]))

    word_contributions.sort(key=lambda x: x[1], reverse=True)

    replaced_count = 0
    for word, score in word_contributions:
        if replaced_count >= max_removals:
            break
        if score <= 0.1: # Lowered threshold so more words qualify
            break

        # If it has a known safe replacement, use it
        replacement = ML_SAFE_REPLACEMENTS.get(word)
        
        # If no strict replacement exists, we NEVER blindly delete (preserves grammar).
        # We simply skip this word and let the email keep it.
        if replacement is None:
            continue

        # Apply the replacement
        if replacement != word:
            # If replacing with empty string (from dictionary), clean up surrounding whitespace
            if replacement == "":
                pattern = re.compile(r'\b' + re.escape(word) + r'\b\s?', re.I)
                new_text = pattern.sub('', text)
            else:
                pattern = re.compile(r'\b' + re.escape(word) + r'\b', re.I)
                new_text = pattern.sub(replacement, text)
                
            if new_text != text:
                text = new_text
                replaced_count += 1

    return text


def apply_punctuation_filter(text):
    """Layer 3: Fix excessive punctuation."""
    text = re.sub(r'!{2,}', '.', text)
    text = re.sub(r'\?{2,}', '?', text)
    text = re.sub(r'\.{4,}', '...', text)
    excl_count = text.count('!')
    if excl_count > 1:
        first = text.index('!')
        text = text[:first+1] + text[first+1:].replace('!', '.')
    return text


def apply_caps_filter(text):
    """Layer 4: Remove ALL CAPS words."""
    def fix_caps(match):
        word = match.group(0)
        if len(word) <= 4:
            return word
        return word.capitalize()
    text = re.sub(r'\b[A-Z]{5,}\b', fix_caps, text)
    return text


def apply_link_filter(text):
    """Layer 5: Ensure max 1 URL, kill shorteners."""
    text = re.sub(r'https?://(bit\.ly|tinyurl\.com|goo\.gl|t\.co|ow\.ly)/\S+', '', text)
    urls = re.findall(r'https?://\S+', text)
    if len(urls) > 1:
        for url in urls[1:]:
            text = text.replace(url, '')
    return text


def apply_personalization_filter(text):
    """Layer 6: Fix broken merge tags."""
    text = re.sub(r'\[FIRSTNAME\]', 'there', text, flags=re.I)
    text = re.sub(r'\[FIRST_NAME\]', 'there', text, flags=re.I)
    text = re.sub(r'\[NAME\]', 'there', text, flags=re.I)
    text = re.sub(r'\[COMPANY\]', 'your team', text, flags=re.I)
    text = re.sub(r'\{\{[^}]+\}\}', '', text)
    text = re.sub(r'\[[A-Z_]+\]', '', text)
    return text


def apply_whitespace_cleanup(text):
    """Final pass: Clean up whitespace artifacts."""
    text = re.sub(r'  +', ' ', text)
    text = re.sub(r'\n{3,}', '\n\n', text)
    text = re.sub(r'\n +', '\n', text)
    text = re.sub(r' +([.,;:!?])', r'\1', text)
    text = re.sub(r'([.,;:!?])\1+', r'\1', text)
    return text.strip()


def full_gmail_correction(text, spam_probability=0.5):
    """
    Run correction layers scaled by spam severity:
    - Always: keyword filter, punctuation, caps, links, merge tags, whitespace
    - Only at high confidence (>90%): ML-driven word replacement
    """
    text = apply_keyword_filter(text)
    text = apply_punctuation_filter(text)
    text = apply_caps_filter(text)
    text = apply_link_filter(text)
    text = apply_personalization_filter(text)

    # Use aggressive ML word replacement for emails with a high spam score
    if spam_probability > 0.70:
        text = apply_ml_driven_correction(text)

    text = apply_whitespace_cleanup(text)
    return text


@app.route('/predict', methods=['POST'])
def predict():
    data = request.get_json()
    if not data or 'text' not in data:
        return jsonify({'error': 'No text provided'}), 400

    text = data['text']
    text_vec = vectorizer.transform([text])
    prediction = model.predict(text_vec)[0]
    proba = model.predict_proba(text_vec)[0]

    return jsonify({
        'is_spam': bool(prediction == 1),
        'spam_probability': float(proba[1]),
    })


@app.route('/correct', methods=['POST'])
def correct():
    data = request.get_json()
    if not data or 'variants' not in data:
        return jsonify({'error': 'No variants array provided'}), 400

    variants = data['variants']
    corrected = []
    total_corrections = 0

    for variant in variants:
        subject = variant.get('subject', '')
        body = variant.get('body', '')
        target_email = variant.get('target_email', '')

        combined = subject + ' ' + body
        text_vec = vectorizer.transform([combined])
        prediction = model.predict(text_vec)[0]
        proba = model.predict_proba(text_vec)[0]
        was_spam = bool(prediction == 1)

        original_subject = subject
        original_body = body

        if was_spam:
            spam_prob_float = float(proba[1])
            subject = full_gmail_correction(subject, spam_prob_float)
            body = full_gmail_correction(body, spam_prob_float)
            total_corrections += 1

        # Re-score the corrected version
        corrected_combined = subject + ' ' + body
        corrected_vec = vectorizer.transform([corrected_combined])
        corrected_proba = model.predict_proba(corrected_vec)[0]

        corrected.append({
            'target_email': target_email,
            'subject': subject,
            'body': body,
            'original_subject': original_subject,
            'original_body': original_body,
            'was_spam': was_spam,
            'spam_probability': round(float(proba[1]) * 100, 1),
            'corrected_spam_probability': round(float(corrected_proba[1]) * 100, 1),
        })

    return jsonify({
        'variants': corrected,
        'total_corrections': total_corrections,
    })


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5050)
