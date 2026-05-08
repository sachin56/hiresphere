#!/usr/bin/env bash
# Bootstrap EKS cluster for HireSphere after CloudFormation stack is deployed
set -euo pipefail

ENVIRONMENT="${1:-production}"
AWS_REGION="${AWS_REGION:-us-east-1}"
CLUSTER_NAME="hiresphere-${ENVIRONMENT}"
K8S_NAMESPACE="hiresphere"

echo "==> Setting up EKS cluster: $CLUSTER_NAME"

# ── 1. Update kubeconfig ──────────────────────────────────────────────────
echo "--> Updating kubeconfig..."
aws eks update-kubeconfig --name "$CLUSTER_NAME" --region "$AWS_REGION"

# ── 2. Install AWS Load Balancer Controller ───────────────────────────────
echo "--> Installing AWS Load Balancer Controller..."
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)

# Add EKS chart repo
helm repo add eks https://aws.github.io/eks-charts 2>/dev/null || true
helm repo update

# Install / upgrade LBC
helm upgrade --install aws-load-balancer-controller eks/aws-load-balancer-controller \
  --namespace kube-system \
  --set clusterName="$CLUSTER_NAME" \
  --set serviceAccount.create=true \
  --set serviceAccount.annotations."eks\.amazonaws\.com/role-arn"="arn:aws:iam::${AWS_ACCOUNT_ID}:role/hiresphere-${ENVIRONMENT}-eks-node-role" \
  --wait

# ── 3. Install Metrics Server (required for HPA) ──────────────────────────
echo "--> Installing Metrics Server..."
kubectl apply -f https://github.com/kubernetes-sigs/metrics-server/releases/latest/download/components.yaml

# ── 4. Create namespace & service account ────────────────────────────────
echo "--> Applying namespace..."
kubectl apply -f kubernetes/namespace.yaml

# ── 5. Create secrets (interactive) ──────────────────────────────────────
echo ""
echo "--> Creating Kubernetes secrets..."
echo "    (You can also apply kubernetes/secrets/secrets-template.yaml manually)"
echo ""

APP_KEY=$(php artisan key:generate --show 2>/dev/null || echo "base64:REPLACE_ME")

read -rsp "  DB password: " DB_PASS; echo ""
read -rsp "  Cognito client secret: " COGNITO_SECRET; echo ""
read -rsp "  Stripe secret key: " STRIPE_KEY; echo ""
read -rsp "  Stripe webhook secret: " STRIPE_WEBHOOK; echo ""
read -rsp "  Daily.co API key: " DAILY_KEY; echo ""

kubectl create secret generic hiresphere-secrets \
  --namespace "$K8S_NAMESPACE" \
  --from-literal="APP_KEY=${APP_KEY}" \
  --from-literal="DB_PASSWORD=${DB_PASS}" \
  --from-literal="COGNITO_CLIENT_SECRET=${COGNITO_SECRET}" \
  --from-literal="STRIPE_SECRET_KEY=${STRIPE_KEY}" \
  --from-literal="STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK}" \
  --from-literal="DAILY_API_KEY=${DAILY_KEY}" \
  --dry-run=client -o yaml | kubectl apply -f -

echo ""
echo "--> Secrets created."

# ── 6. Apply ConfigMap ────────────────────────────────────────────────────
echo "--> Applying ConfigMap..."
kubectl apply -f kubernetes/configmaps/

# ── 7. Apply all manifests ────────────────────────────────────────────────
echo "--> Applying Kubernetes manifests..."
kubectl apply -f kubernetes/deployments/
kubectl apply -f kubernetes/services/
kubectl apply -f kubernetes/ingress/
kubectl apply -f kubernetes/hpa/

# ── 8. Wait for rollout ───────────────────────────────────────────────────
echo "--> Waiting for deployment rollout..."
kubectl rollout status deployment/hiresphere-api \
  -n "$K8S_NAMESPACE" --timeout=300s

echo ""
echo "==> EKS setup complete!"
kubectl get all -n "$K8S_NAMESPACE"
echo ""
ALB=$(kubectl get ingress hiresphere-ingress -n "$K8S_NAMESPACE" \
  -o jsonpath='{.status.loadBalancer.ingress[0].hostname}' 2>/dev/null || echo "(pending)")
echo "    ALB DNS: $ALB"
echo "    Point api.hiresphere.io CNAME to the ALB DNS above."
