<?php

declare(strict_types=1);

namespace Terminal42\FolderpageBundle\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ResetInterface;

class PageTypeAccessVoter implements CacheableVoterInterface, ResetInterface
{
    public function __construct(
        private readonly CacheableVoterInterface $inner,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $this->inner->supportsAttribute($attribute);
    }

    public function supportsType(string $subjectType): bool
    {
        return $this->inner->supportsType($subjectType);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        $result = $this->inner->vote($token, $subject, $attributes);

        // Only check permissions if access is denied
        if (
            VoterInterface::ACCESS_DENIED !== $result
            || (!$subject instanceof CreateAction && !$subject instanceof UpdateAction)
        ) {
            return $result;
        }

        if ($this->validateCreateOrUpdateFolder($token, $subject) || $this->validateMoveToFolder($subject)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return $result;
    }

    private function validateCreateOrUpdateFolder(TokenInterface $token, CreateAction|UpdateAction $subject): bool
    {
        $types = [];

        if ($subject instanceof UpdateAction && isset($subject->getCurrent()['type'])) {
            $types[] = $subject->getCurrent()['type'];
        }

        if (isset($subject->getNew()['type'])) {
            $types[] = $subject->getNew()['type'];
        }

        if (!\in_array('folder', $types, true)) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE], 'folder');
    }

    private function validateMoveToFolder(CreateAction|UpdateAction $subject): bool
    {
        if ($subject instanceof CreateAction || !$subject->getNewPid()) {
            return false;
        }

        return 'folder' === $this->connection->fetchOne('SELECT type FROM tl_page WHERE id=?', [$subject->getNewPid()]);
    }

    public function reset(): void
    {
        if ($this->inner instanceof ResetInterface) {
            $this->inner->reset();
        }
    }
}
