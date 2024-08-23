#!/usr/bin/env bash
set -e

if [ "$#" -ne 2 ]; then
  echo "Usage: $0 <repository> <tag-masks>"
  exit 1
fi

echo $GH_CONTAINER_REGISTRY_TOKEN | skopeo login ghcr.io --username $GH_CONTAINER_REGISTRY_USER --password-stdin > /dev/null 2>&1 &
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
    echo $GH_CONTAINER_REGISTRY_TOKEN | docker login ghcr.io --username $GH_CONTAINER_REGISTRY_USER --password-stdin > /dev/null 2>&1 &
    for tag in ${MATCHING_TAGS[@]}
    do
        HASHES_TO_SKIP+=($(docker manifest inspect $1:$tag | jq -r '.manifests[] | .digest'))
    done
fi

if [ ${#HASHES_TO_SKIP[@]} -eq 0 ]; then
    echo "No matching tags found. Something likely went wrong."
    exit 1
fi

echo "hashes-to-skip=${HASHES_TO_SKIP[@]}" | tr " " ","
