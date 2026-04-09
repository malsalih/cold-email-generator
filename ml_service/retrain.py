import os
import glob
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import accuracy_score, classification_report
import joblib

DATA_DIR = '../app/learn_data'

def normalize_dataframe(df, filename):
    print(f"Normalizing {filename}...")
    
    # Standardize column names
    df.columns = [str(col).strip().lower() for col in df.columns]
    
    message_col = None
    label_col = None
    
    # Find label column
    if 'label' in df.columns:
        label_col = 'label'
    elif 'v1' in df.columns:
        label_col = 'v1'
    elif 'category' in df.columns:
        label_col = 'category'
        
    # Find text column
    if 'text' in df.columns:
        message_col = 'text'
    elif 'v2' in df.columns:
        message_col = 'v2'
    elif 'message' in df.columns:
        message_col = 'message'
    elif 'body' in df.columns:
        # Check if subject exists to concatenate
        if 'subject' in df.columns:
            df['combined_text'] = df['subject'].fillna('') + " " + df['body'].fillna('')
            message_col = 'combined_text'
        else:
            message_col = 'body'

    if not label_col or not message_col:
        print(f"  -> Skipping {filename}: Missing known columns.")
        return None
        
    # Keep only target columns
    df_clean = df[[label_col, message_col]].copy()
    df_clean.columns = ['label', 'message']
    
    # Drop NAs
    df_clean = df_clean.dropna()
    
    # Normalize labels to binary 1=spam, 0=ham
    # Some datasets use 1/0, some 'spam'/'ham'
    def map_label(x):
        val = str(x).strip().lower()
        if val in ['spam', '1', '1.0', 'true', 'yes']:
            return 1
        return 0
        
    df_clean['label'] = df_clean['label'].apply(map_label)
    
    return df_clean

# 1. Load Data
all_dfs = []
csv_files = glob.glob(os.path.join(DATA_DIR, '**/*.csv'), recursive=True)

print(f"Found {len(csv_files)} CSV files. Ingesting...")

for file in csv_files:
    try:
        # Some datasets use varying encodings
        df_raw = pd.read_csv(file, encoding='utf-8', on_bad_lines='skip', low_memory=False)
    except UnicodeDecodeError:
        df_raw = pd.read_csv(file, encoding='latin-1', on_bad_lines='skip', low_memory=False)
    except Exception as e:
        print(f"Error reading {file}: {e}")
        continue
        
    df_norm = normalize_dataframe(df_raw, os.path.basename(file))
    if df_norm is not None:
        all_dfs.append(df_norm)

if not all_dfs:
    print("No valid data found to train!")
    exit(1)

# Concatenate all dataframes
final_df = pd.concat(all_dfs, ignore_index=True)
# Drop duplicates
final_df = final_df.drop_duplicates()

print(f"\n--- INGESTION COMPLETE ---")
print(f"Total Unique Combined Emails: {len(final_df):,}")
print(f"Spam: {final_df[final_df['label'] == 1].shape[0]:,}")
print(f"Ham: {final_df[final_df['label'] == 0].shape[0]:,}")

X = final_df['message']
y = final_df['label']

# 2. Train-Test Split (80% / 20%)
print("\nSplitting and Vectorizing dataset... (this may take a moment)")
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# 3. TF-IDF Vectorization
# Expanded vocabulary to 50,000 to catch human idiosyncrasies and rich patterns
vectorizer = TfidfVectorizer(stop_words='english', lowercase=True, max_features=50000)
X_train_vec = vectorizer.fit_transform(X_train)
X_test_vec = vectorizer.transform(X_test)

print("Training Naive Bayes model on Massive Dataset...")

# 4. Train MultinomialNB Model
model = MultinomialNB()
model.fit(X_train_vec, y_train)

print("\n--- Evaluation ---")
# 5. Evaluate the model
y_pred = model.predict(X_test_vec)

accuracy = accuracy_score(y_test, y_pred)
print(f"Accuracy: {accuracy * 100:.2f}%")
print("\nClassification Report:")
print(classification_report(y_test, y_pred, target_names=['Ham (Clear)', 'Spam (Block)']))

print("\n--- Exporting ---")
# 6. Save Model and Vectorizer to disk
joblib.dump(model, 'spam_model.pkl')
joblib.dump(vectorizer, 'vectorizer.pkl')
print("SUPER-MODEL EXPORTED to spam_model.pkl and vectorizer.pkl successfully!")
