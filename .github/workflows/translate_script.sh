#!/bin/bash

# Set your Google Cloud Translation API endpoint
TRANSLATE_API_ENDPOINT="https://translation.googleapis.com/v3/projects/redcap-364614/locations/us-central1:batchTranslateText"

# Set the source language and target languages
SOURCE_LANG="en"
TARGET_LANGUAGES="$1"

# Set the path to the input file
INPUT_FILE="gs://sag_readme/README.html"


# Construct the API request JSON
API_REQUEST='{
"sourceLanguageCode": "'$SOURCE_LANG'",
"targetLanguageCodes": '$TARGET_LANGUAGES',
"inputConfigs": [{
    "gcsSource": {
    "inputUri": "'$INPUT_FILE'"
    },
    "mimeType": "text/html" 
}],
"outputConfig": {
    "gcsDestination": {
    "outputUriPrefix": "gs://sag_readme_translated/"
    }
}
}'

# Make the API request
API_RESPONSE=$(curl -s -X POST \
-H "Authorization: Bearer $GCP_ACCESS_TOKEN" \
-H "Content-Type: application/json" \
-H "x-goog-user-project: redcap-364614" \
--data "$API_REQUEST" \
"$TRANSLATE_API_ENDPOINT")

# Extract the operation ID from the response
OPERATION_ID=$(echo "$API_RESPONSE" | jq -r '.name')

echo "OPERATION_ID=$OPERATION_ID" >> $GITHUB_OUTPUT
