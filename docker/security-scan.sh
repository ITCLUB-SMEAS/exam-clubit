#!/bin/bash
# ==========================================================================
# Docker Security Scanning Script
# Requires: docker, trivy (optional), docker-bench-security (optional)
# ==========================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Docker Security Scan                  ${NC}"
echo -e "${GREEN}========================================${NC}"

# ==========================================================================
# 1. Check Docker daemon configuration
# ==========================================================================
echo -e "\n${YELLOW}[1/5] Checking Docker daemon...${NC}"

if docker info --format '{{.SecurityOptions}}' | grep -q "seccomp"; then
    echo -e "${GREEN}✓ Seccomp enabled${NC}"
else
    echo -e "${RED}✗ Seccomp not enabled${NC}"
fi

if docker info --format '{{.SecurityOptions}}' | grep -q "apparmor"; then
    echo -e "${GREEN}✓ AppArmor enabled${NC}"
else
    echo -e "${YELLOW}! AppArmor not detected (may use SELinux)${NC}"
fi

# ==========================================================================
# 2. Scan images for vulnerabilities (if Trivy installed)
# ==========================================================================
echo -e "\n${YELLOW}[2/5] Scanning images for vulnerabilities...${NC}"

if command -v trivy &> /dev/null; then
    echo "Scanning cbt-app image..."
    trivy image --severity HIGH,CRITICAL cbt-app:latest || true
    
    echo "Scanning cbt-nginx image..."
    trivy image --severity HIGH,CRITICAL cbt-nginx:latest || true
else
    echo -e "${YELLOW}! Trivy not installed. Install with:${NC}"
    echo "  curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin"
fi

# ==========================================================================
# 3. Check container configurations
# ==========================================================================
echo -e "\n${YELLOW}[3/5] Checking container configurations...${NC}"

for container in cbt-app cbt-nginx cbt-mysql cbt-redis cbt-queue cbt-scheduler; do
    if docker ps -q -f name=$container &> /dev/null; then
        echo -e "\n${GREEN}Container: $container${NC}"
        
        # Check if running as root
        USER=$(docker inspect --format '{{.Config.User}}' $container 2>/dev/null || echo "unknown")
        if [ "$USER" = "root" ] || [ -z "$USER" ]; then
            echo -e "${RED}  ✗ Running as root${NC}"
        else
            echo -e "${GREEN}  ✓ Running as non-root user: $USER${NC}"
        fi
        
        # Check read-only filesystem
        READONLY=$(docker inspect --format '{{.HostConfig.ReadonlyRootfs}}' $container 2>/dev/null || echo "false")
        if [ "$READONLY" = "true" ]; then
            echo -e "${GREEN}  ✓ Read-only filesystem${NC}"
        else
            echo -e "${YELLOW}  ! Filesystem is writable${NC}"
        fi
        
        # Check privileged mode
        PRIVILEGED=$(docker inspect --format '{{.HostConfig.Privileged}}' $container 2>/dev/null || echo "false")
        if [ "$PRIVILEGED" = "false" ]; then
            echo -e "${GREEN}  ✓ Not privileged${NC}"
        else
            echo -e "${RED}  ✗ Running in privileged mode!${NC}"
        fi
        
        # Check capabilities
        CAP_DROP=$(docker inspect --format '{{.HostConfig.CapDrop}}' $container 2>/dev/null || echo "[]")
        if echo "$CAP_DROP" | grep -q "ALL"; then
            echo -e "${GREEN}  ✓ All capabilities dropped${NC}"
        else
            echo -e "${YELLOW}  ! Not all capabilities dropped${NC}"
        fi
    fi
done

# ==========================================================================
# 4. Check network isolation
# ==========================================================================
echo -e "\n${YELLOW}[4/5] Checking network isolation...${NC}"

# Check if backend network is internal
BACKEND_INTERNAL=$(docker network inspect backend --format '{{.Internal}}' 2>/dev/null || echo "unknown")
if [ "$BACKEND_INTERNAL" = "true" ]; then
    echo -e "${GREEN}✓ Backend network is internal (isolated)${NC}"
else
    echo -e "${YELLOW}! Backend network is not internal${NC}"
fi

# ==========================================================================
# 5. Check secrets
# ==========================================================================
echo -e "\n${YELLOW}[5/5] Checking secrets management...${NC}"

SECRETS_DIR="$(dirname "$0")/secrets"
if [ -d "$SECRETS_DIR" ]; then
    PERMS=$(stat -c %a "$SECRETS_DIR" 2>/dev/null || stat -f %Lp "$SECRETS_DIR" 2>/dev/null)
    if [ "$PERMS" = "700" ]; then
        echo -e "${GREEN}✓ Secrets directory has correct permissions (700)${NC}"
    else
        echo -e "${RED}✗ Secrets directory permissions: $PERMS (should be 700)${NC}"
    fi
    
    for secret in app_key.txt db_root_password.txt db_password.txt redis_password.txt; do
        if [ -f "$SECRETS_DIR/$secret" ]; then
            PERMS=$(stat -c %a "$SECRETS_DIR/$secret" 2>/dev/null || stat -f %Lp "$SECRETS_DIR/$secret" 2>/dev/null)
            if [ "$PERMS" = "600" ]; then
                echo -e "${GREEN}✓ $secret has correct permissions (600)${NC}"
            else
                echo -e "${RED}✗ $secret permissions: $PERMS (should be 600)${NC}"
            fi
        else
            echo -e "${YELLOW}! $secret not found${NC}"
        fi
    done
else
    echo -e "${YELLOW}! Secrets directory not found${NC}"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  Scan Complete                         ${NC}"
echo -e "${GREEN}========================================${NC}"
