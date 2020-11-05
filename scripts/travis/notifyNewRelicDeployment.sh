#!/usr/bin/env sh
if [ ! -z "${NR_API_KEY}" ]; then
  if [ "$TRAVIS_BRANCH" != "dev" ] && [ "$TRAVIS_BRANCH" != "prod" ]; then
    exit 0
  fi

  NR_APP_NAME=$(basename "${TRAVIS_REPO_SLUG}")
  if [ "$TRAVIS_BRANCH" = "dev" ]; then
    NR_APP_NAME="dev-$NR_APP_NAME"
  fi

  COMMITTER_EMAIL=$(git log -1 $TRAVIS_COMMIT --pretty="%cE")
  NR_APP_ID=$(curl -X GET 'https://api.newrelic.com/v2/applications.json' \
      -H "X-Api-Key:${NR_API_KEY}" \
      -G -d "filter[name]=${NR_APP_NAME}" -s | jq -r '.applications[0].id')

  curl -X POST "https://api.newrelic.com/v2/applications/${NR_APP_ID}/deployments.json" \
       -H "X-Api-Key:${NR_API_KEY}" -i \
       -H 'Content-Type: application/json' \
       -d \
  "{
    \"deployment\": {
      \"revision\": \"${TRAVIS_COMMIT}\",
      \"changelog\": \"${TRAVIS_COMMIT_MESSAGE}\",
      \"user\": \"${COMMITTER_EMAIL}\"
    }
  }"
fi
