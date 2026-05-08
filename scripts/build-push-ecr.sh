#!/usr/bin/env bash
# Build the Docker image locally and push to ECR
set -euo pipefail

ENVIRONMENT="${1:-production}"
AWS_REGION="${AWS_REGION:-us-east-1}"
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
ECR_REPO="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/hiresphere/api"
TAG="${2:-$(git rev-parse --short HEAD)}"

echo "==> Building HireSphere API image"
echo "    ECR:  $ECR_REPO"
echo "    Tag:  $TAG"

# Login to ECR
aws ecr get-login-password --region "$AWS_REGION" | \
  docker login --username AWS --password-stdin \
  "${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"

# Build
docker build \
  --tag "${ECR_REPO}:${TAG}" \
  --tag "${ECR_REPO}:latest" \
  --build-arg "BUILD_DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  --build-arg "GIT_SHA=$(git rev-parse HEAD)" \
  --platform linux/amd64 \
  .

# Push both tags
docker push "${ECR_REPO}:${TAG}"
docker push "${ECR_REPO}:latest"

echo ""
echo "==> Pushed: ${ECR_REPO}:${TAG}"
echo "    To deploy: kubectl set image deployment/hiresphere-api api=${ECR_REPO}:${TAG} -n hiresphere"
