#!/usr/bin/env bash
set -e

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <repository> <tag-masks>"
  exit 1
fi

ALL_TAGS=$(skopeo list-tags docker://$1 | jq -r '.Tags[]')
MATCHING_TAGS=()

for tag in $ALL_TAGS
do
    # Check if the tag matches one of the CSV deliniated masks.
    for mask in $(echo $2 | tr "," "\n" | sort -u)
    do
        if [[ $tag == $mask ]]; then
            MATCHING_TAGS+=("$tag")
        fi
    done
done

HASHES_TO_SKIP=()
if [ ! ${#MATCHING_TAGS[@]} -eq 0 ]; then
    for TAG in ${MATCHING_TAGS[@]}
    do
        MAINFEST_METADATA=$(docker manifest inspect $1:$TAG)
        if [[ $MAINFEST_METADATA == *"manifests"* ]]; then
            # Multiarch.
            HASHES_TO_SKIP+=($(echo "$MAINFEST_METADATA" | jq -r '.manifests[] | .digest'))
        else
            # Single arch.
            HASHES_TO_SKIP+=($(echo "$MAINFEST_METADATA" | jq -r '.config.digest'))
        fi
    done
fi

if [ ${#HASHES_TO_SKIP[@]} -eq 0 ]; then
    echo "No matching tags found. Something likely went wrong."
    exit 1
fi

echo "hashes-to-skip=${HASHES_TO_SKIP[@]}" | tr " " ","
