#!/bin/sh

# Install kubectl and set config
curl -LO https://storage.googleapis.com/kubernetes-release/release/$(curl -s https://storage.googleapis.com/kubernetes-release/release/stable.txt)/bin/linux/amd64/kubectl
chmod +x ./kubectl
sudo mv ./kubectl /usr/local/bin/kubectl

# Copy Config
mkdir ${HOME}/.kube
KUBECONFIG_FILE="${HOME}/.kube/config"
cp vendor/unb-libraries/cargodock/data/kubectl/config ${KUBECONFIG_FILE}

# Replace tokens in config
sed -i -e 's|KUBE_CA_CERT|'"${KUBE_CA_CERT}"'|g' ${KUBECONFIG_FILE}
sed -i -e 's|KUBE_ENDPOINT|'"${KUBE_ENDPOINT}"'|g' ${KUBECONFIG_FILE}
sed -i -e 's|KUBE_CLUSTER_NAME|'"${KUBE_CLUSTER_NAME}"'|g' ${KUBECONFIG_FILE}
sed -i -e 's|KUBE_USERNAME|'"${KUBE_USERNAME}"'|g' ${KUBECONFIG_FILE}
sed -i -e 's|KUBE_USER_TOKEN|'"${KUBE_USER_TOKEN}"'|g' ${KUBECONFIG_FILE}
