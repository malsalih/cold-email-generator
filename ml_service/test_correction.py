import requests, json

# Test 1: Clean Gemini email (83% spam — borderline, should apply light fixes only)
print("=" * 60)
print("TEST 1: Clean domain pitch (borderline spam score)")
print("=" * 60)
r = requests.post('http://127.0.0.1:5000/correct', json={'variants': [{
    'target_email': 'ceo@target.com',
    'subject': 'Quick note about brand-boost.com',
    'body': 'Hi there,\n\nI noticed your company is operating under a different domain. I represent the owner of brand-boost.com and wanted to see if acquiring this premium domain might be of interest to strengthen your online presence and brand authority.\n\nWould you be open to a brief conversation about this?\n\nBest regards,\nJohn'
}]})
v = r.json()['variants'][0]
print(f"Was spam: {v['was_spam']}")
print(f"Gemini score: {v['spam_probability']}%  ->  After fix: {v['corrected_spam_probability']}%")
changed = v['original_body'] != v['body']
print(f"Text changed: {changed}")
if changed:
    print(f"\nCORRECTED:\n{v['body']}")

# Test 2: Very spammy email (should trigger ML word replacement)
print("\n" + "=" * 60)
print("TEST 2: Heavily spammy email (should trigger ML correction)")
print("=" * 60)
r2 = requests.post('http://127.0.0.1:5000/correct', json={'variants': [{
    'target_email': 'ceo@test.com',
    'subject': 'AMAZING OFFER - Subscribe Now!!!',
    'body': 'Dear Sir/Madam,\n\nCongratulations! You have been selected to purchase this incredible deal! Click here to claim your exclusive offer and save massive amounts of cash!!!\n\nThis promotion expires today. Act now or you will lose this once in a lifetime chance!\n\nSign up free at https://bit.ly/deal and https://spammy.com/offer'
}]})
v2 = r2.json()['variants'][0]
print(f"Was spam: {v2['was_spam']}")
print(f"Gemini score: {v2['spam_probability']}%  ->  After fix: {v2['corrected_spam_probability']}%")
print(f"\nORIGINAL:\n{v2['original_body']}")
print(f"\nCORRECTED:\n{v2['body']}")
