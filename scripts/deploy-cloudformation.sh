#!/usr/bin/env bash
# Deploy HireSphere CloudFormation nested stacks
set -euo pipefail

ENVIRONMENT="${1:-production}"
AWS_REGION="${AWS_REGION:-us-east-1}"
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
TEMPLATES_BUCKET="hiresphere-${ENVIRONMENT}-cf-templates-${AWS_ACCOUNT_ID}"
STACK_NAME="hiresphere-${ENVIRONMENT}"

echo "==> Deploying HireSphere ($ENVIRONMENT) in $AWS_REGION"
echo "    Account: $AWS_ACCOUNT_ID"
echo "    Stack:   $STACK_NAME"
echo ""

# ── 1. Upload nested templates to S3 ─────────────────────────────────────
echo "--> Uploading CloudFormation templates..."
aws s3 mb "s3://${TEMPLATES_BUCKET}" --region "$AWS_REGION" 2>/dev/null || true
aws s3 sync cloudformation/ "s3://${TEMPLATES_BUCKET}/cloudformation/" \
  --exclude "*.DS_Store" \
  --delete

TEMPLATES_BASE_URL="https://${TEMPLATES_BUCKET}.s3.${AWS_REGION}.amazonaws.com/cloudformation"
echo "    Templates URL: $TEMPLATES_BASE_URL"

# ── 2. Collect required parameters ───────────────────────────────────────
echo ""
echo "--> Enter required parameters (press Enter to accept default):"

read -rp "  GitHub repo (owner/repo) [hiresphere/platform]: " GITHUB_REPO
GITHUB_REPO="${GITHUB_REPO:-hiresphere/platform}"

read -rsp "  GitHub access token: " GITHUB_TOKEN
echo ""

read -rsp "  RDS master password: " DB_PASSWORD
echo ""

read -rsp "  Stripe secret key: " STRIPE_KEY
echo ""

read -rsp "  Daily.co API key: " DAILY_KEY
echo ""

# ── 3. Deploy / update master stack ──────────────────────────────────────
echo ""
echo "--> Deploying master stack..."

aws cloudformation deploy \
  --stack-name "$STACK_NAME" \
  --template-file cloudformation/00-main.yaml \
  --capabilities CAPABILITY_IAM CAPABILITY_NAMED_IAM CAPABILITY_AUTO_EXPAND \
  --region "$AWS_REGION" \
  --parameter-overrides \
    Environment="$ENVIRONMENT" \
    TemplatesBucketName="$TEMPLATES_BUCKET" \
    TemplatesBaseURL="$TEMPLATES_BASE_URL" \
    GitHubRepository="$GITHUB_REPO" \
    GitHubAccessToken="$GITHUB_TOKEN" \
    DBMasterPassword="$DB_PASSWORD" \
    StripeSecretKey="$STRIPE_KEY" \
    DailyApiKey="$DAILY_KEY" \
  --tags \
    Project=HireSphere \
    Environment="$ENVIRONMENT" \
    ManagedBy=CloudFormation

# ── 4. Output stack resources ─────────────────────────────────────────────
echo ""
echo "==> Stack deployed successfully. Key outputs:"
aws cloudformation describe-stacks \
  --stack-name "$STACK_NAME" \
  --region "$AWS_REGION" \
  --query 'Stacks[0].Outputs[*].[OutputKey,OutputValue]' \
  --output table

echo ""
echo "==> Next steps:"
echo "    1. Update kubeconfig: aws eks update-kubeconfig --name hiresphere-${ENVIRONMENT}"
echo "    2. Apply K8s manifests: kubectl apply -f kubernetes/"
echo "    3. Trigger GitHub Actions deploy from the main branch"
