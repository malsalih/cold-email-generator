import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import accuracy_score, classification_report
import joblib

print("Starting Phase 2 & 3: Preprocessing & Training...")

# 1. Load the dataset
try:
    df = pd.read_csv('spam_dataset.csv')
except FileNotFoundError:
    print("Error: spam_dataset.csv not found. Please run setup_data.py first.")
    exit(1)

# Ensure valid data
df = df.dropna(subset=['message', 'label'])

X = df['message']
# Convert label to binary: 1 if spam, 0 if ham
y = df['label'].apply(lambda x: 1 if x.strip().lower() == 'spam' else 0)

# 2. Train-Test Split (80% / 20%)
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# 3. TF-IDF Vectorization
# Removes English stop words and converts text to feature matrices
vectorizer = TfidfVectorizer(stop_words='english', lowercase=True, max_features=5000)
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

print("Data vectorized. Training Naive Bayes model...")

# 4. Train MultinomialNB Model
model = MultinomialNB()
model.fit(X_train_vec, y_train)

print("\n--- Phase 4: Evaluation ---")
# 5. Evaluate the model
y_pred = model.predict(X_test_vec)

accuracy = accuracy_score(y_test, y_pred)
print(f"Accuracy: {accuracy * 100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_test, y_pred, target_names=['Ham (Clear)', 'Spam (Block)']))

print("\n--- Phase 5: Deployment Export ---")
# 6. Save Model and Vectorizer to disk
joblib.dump(model, 'spam_model.pkl')
joblib.dump(vectorizer, 'vectorizer.pkl')
print("Exported spam_model.pkl and vectorizer.pkl successfully!")
