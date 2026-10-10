import re

# Common words to remove so the model focuses on informative ones. Add more as needed.
STOPWORDS = {"ang", "sa", "ng", "na", "po", "ako", "the", "is", "at", "mga",
             "dito", "namin", "ko", "may", "ay", "amin", "aming", "lugar", "street"} # NEW WORDS ADDED AFTER FIRST TEST "amin, aming, lugar, street"

def clean(text):
    text = text.lower() # Turns the text lowercase
    text = re.sub(r"[^a-z0-9\s]", " ", text) # Replaces special characters into spaces
    return " ".join(w for w in text.split()
                    if w not in STOPWORDS) # text.split chops the strings into a list of words, "if w not in STOPWORDS" ensures it only keeps words that aren't in the STOPWORDS, ".join" glues the list back into one string.