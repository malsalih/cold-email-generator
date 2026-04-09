import pandas as pd
import requests
import io
import os

print("Starting Phase 1: Downloading standard Spam/Ham Dataset...")

# URL for a reliable, standard public spam classification CSV (Spam/Ham labels)
url = "https://raw.githubusercontent.com/mohitgupta-omg/Kaggle-SMS-Spam-Collection-Dataset-/master/spam.csv"

try:
    response = requests.get(url)
    response.raise_for_status()
    
    # Dataset often has weird encoding from Kaggle
    df = pd.read_csv(io.StringIO(response.content.decode('latin-1')))
    
    # Standardize columns to "label" and "text"
    df = df[['v1', 'v2']]
    df.columns = ['label', 'message']
    
    # Standardize labels to lowercase (spam / ham)
    df['label'] = df['label'].str.lower()
    
    print(f"Dataset successfully downloaded! Row count: {len(df)}")
    print(df.head())
    
    # Save locally to ensure Phase 2 & 3 run quickly
    df.to_csv("spam_dataset.csv", index=False)
    print("Dataset saved locally to 'ml_service/spam_dataset.csv'")

except Exception as e:
    print(f"Error fetching dataset: {e}")
