<?php
namespace AppBundle\Utils;

use AppBundle\Entity\Achievement;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GameProfile
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var User
     */
    private $user;
    /**
     * @var int
     */
    private $id;
    /**
     * @var ProfileHelper
     */
    private $helper;
    /**
     * @var Achievement|null
     */
    private $achievements = null;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        SessionInterface $session,
        ProfileHelper $helper,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->session = $session;
        $this->helper = $helper;
        $this->tokenStorage = $tokenStorage;
    }

    public function getUserProfile(int $userId, int $yourId): ?User
    {
        $this->user = $this->em->getRepository('AppBundle:User')->find($userId);
        if (! $this->user instanceof User) {
            $this->session->getFlashBag()->add('error', 'Użytkownik nie  znaleziony');
            return null;
        }
        $this->id = $yourId;
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->id = $this->tokenStorage->getToken()->getUser()->getId();
    }

    public function getUsersTeam(): ?array
    {
        if ($this->checkIfUserBlockedTeamView()) {
            return null;
        }
        return $this->em->getRepository('AppBundle:Pokemon')
                    ->getUsersPokemonsFromTeam($this->user)['pokemons'];
    }

    public function getUserSkills(): array
    {
        $userSkills = [];
        $skills = $this->helper->getSkills();
        $count = count($skills);
        $userGetSkills = $this->user->getSkills();
        for ($i = 0; $i < $count; $i++) {
            $need = explode(';', $skills[$i]['need']);
            $needInfo = $this->getFromDb($need[0]);
            $quantity = $needInfo->{'get'.$need[1]}();

            $userSkills[] = [
                'skillInfo' => $skills[$i],
                'level' => $userGetSkills->{'getSkill'.($i+1)}(),
                'userQuantity' => $quantity
            ];
        }
        return $userSkills;
    }

    public function usePoints(int $i, User $user): void
    {
        $skills = $this->helper->getSkill($i);
        if (!$skills) {
            $this->session->getFlashBag()->add('error', 'Błędne ID umiejętności');
            return;
        }
        $this->id = $user->getId();
        $userGetSkills = $user->getSkills();
        $need = explode(';', $skills['need']);
        $needInfo = $this->getFromDb($need[0]);
        $quantity = $needInfo->{'get'.$need[1]}();
        if (($userGetSkills->{'getSkill'.($i+1)}() + 1) > $skills['max']) {
            $this->session->getFlashBag()->add('error', 'Już osiągnięto maksymalny poziom');
            return;
        }
        if ($skills[($userGetSkills->{'getSkill'.($i+1)}()+1).'_need'] > $quantity) {
            $this->session->getFlashBag()->add('error', 'Nie spełniono wymagań');
            return;
        }
        if ($user->getPoints() < $skills[($userGetSkills->{'getSkill'.($i+1)}()+1)]) {
            $this->session->getFlashBag()->add('error', 'Masz za mało punktów umiejętności');
            return;
        }
        $user->setPoints($user->getPoints() - $skills[($userGetSkills->{'getSkill'.($i+1)}()+1)]);
        $userGetSkills->{'setSkill'.($i+1)}($userGetSkills->{'getSkill'.($i+1)}() + 1);
        $this->session->getFlashBag()->add('success', 'Zwiększono umiejętność '.$skills['name']);
        $this->em->flush();
    }

    public function getBadges(): array
    {
        return explode(';', $this->user->getBadges());
    }

    public function getBattle(): bool
    {
        return ($this->id === $this->user->getId());
    }

    public function getFriend(): int
    {
        if ($this->id === $this->user->getId()) {
            return 0;
        }
        $friend = $this->em->getRepository('AppBundle:Friend')
            ->getOneFriendship($this->id, $this->user);
        if ($friend) {
            return 1;
        }
        $invite = $this->em->getRepository('AppBundle:Friend')
            ->checkIfUserSentInvitation($this->id, $this->user);
        if ($invite) {
            return 2;
        }
        $inviteGot = $this->em->getRepository('AppBundle:Friend')
            ->checkIfUserReceivedInvitation($this->id, $this->user);
        if ($inviteGot) {
            return 3;
        }
        return 4;
    }

    public function getUserProfileFromUsername(string $userName): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(['login' => $userName]);
    }

    private function checkIfUserBlockedTeamView(): bool
    {
        $u = explode("|", $this->user->getSettings());
        return !$u[0];
    }

    /**
     * @param string $need
     *
     * @return Achievement|object|null
     */
    private function getFromDb(string $need): object
    {
        switch ($need) {
            case 'Achievement':
                return $this->getAchievements();
        }
    }

    private function getAchievements(): Achievement
    {
        if ($this->achievements === null) {
            $this->achievements = $this->em->find('AppBundle:Achievement', $this->id);
        }
        return $this->achievements;
    }
}
