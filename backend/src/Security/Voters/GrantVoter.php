<?php

namespace App\Security\Voters;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class GrantVoter extends Voter
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const ACCESS = 'ACCESS';

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::GET, self::POST, self::PUT, self::ACCESS, self::DELETE])) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        switch ($attribute) {
            case self::GET:
                return $this->canGet($subject, $user);
            case self::POST:
                return $this->canPost($subject, $user);
            case self::PUT:
                return $this->canPut($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
            case self::ACCESS:
                return $this->canAccess($subject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canGet($object, User $user)
    {
        return true;
    }

    private function canAccess($object, User $user)
    {
        return true;
    }

    private function canDelete($object, User $user)
    {
        return true;
    }

    private function canPut($object, User $user)
    {
        if (get_class($object) === User::class) {
            if ($object !== $user) {
                return false;
            }
        }

        return true;
    }

    private function canPost($object, User $user)
    {
        return true;
    }

}
