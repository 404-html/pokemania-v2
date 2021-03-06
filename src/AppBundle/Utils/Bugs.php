<?php
namespace AppBundle\Utils;

use AppBundle\Entity\Bug;
use AppBundle\Entity\Report;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Bugs
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var Session
     */
    private $session;

    public function __construct(EntityManagerInterface $em, Session $session)
    {
        $this->em = $em;
        $this->session = $session;
    }

    public function list(User $user): array
    {
        $admin = $user->getRoles() === ['ROLE_ADMIN'] ? 1 : 0;
        if ($admin) {
            return $this->getBugsAdmin();
        } else {
            return $this->getBugsUser($user->getId());
        }
    }

    public function add(?string $title, ?string $content, User $user): bool
    {
        if ($title === '') {
            $this->session->getFlashBag()->add('error', 'Tytuł nie może być pusty');
            return false;
        }
        if ($content === '') {
            $this->session->getFlashBag()->add('error', 'Treść nie może być pusta');
            return false;
        }
        $this->addBugToDb($title, $content, $user);
        $this->em->flush();

        $this->session->getFlashBag()->add('success', 'Dodano błąd i powiadomomiono administratora o nim.');
        return true;
    }

    public function delete(int $id): void
    {
        $bug = $this->em->find('AppBundle:Bug', $id);

        if (!$bug) {
            $this->session->getFlashBag()->add('error', 'Błąd nie znaleziony lub usunięty wcześniej.');
            return;
        }
        $this->addReport($bug->getReportedBy(), 'Błąd został usunięty', 'Jeden z Twoich błędów został usunięty');
        $this->em->remove($bug);
        $this->em->flush();
        $this->session->getFlashBag()->add('success', 'Poprawnie usunięto błąd.');
    }

    public function setDone(int $id): void
    {
        $bug = $this->em->find('AppBundle:Bug', $id);
        if (!$bug) {
            $this->session->getFlashBag()->add('error', 'Błąd nie znaleziony lub usunięty wcześniej.');
            return;
        }
        if ($bug->getDone()) {
            $this->session->getFlashBag()->add('error', 'Błąd został już poprawiony wcześniej.');
            return;
        }
        $this->addReport($bug->getReportedBy(), 'Błąd został poprawiony', 'Jeden z Twoich błędów został poprawiony');
        $bug->setDone(1);
        $this->em->persist($bug);
        $this->em->flush();
        $this->session->getFlashBag()->add('success', 'Poprawniono błąd.');
    }

    private function getBugsAdmin(): array
    {
        return $this->em->getRepository('AppBundle:Bug')->findAll();
    }

    private function getBugsUser(int $userId): array
    {
        return $this->em->getRepository('AppBundle:Bug')->findBy(['reportedBy' => $userId]);
    }

    private function addBugToDb(string $title, string $content, User $user): void
    {
        $bug = new Bug();
        $bug->setContent($content);
        $bug->setTitle($title);
        $bug->setDone(0);
        $bug->setReportedBy($user);
        $bug->setTime(new \DateTime());

        $this->em->persist($bug);
    }

    private function addReport(User $user, string $title, string $content = ''): void
    {
        $report = new Report();
        $report->setIsRead(0);
        $report->setTime(new \DateTime());
        $report->setTitle($title);
        $report->setContent($content);
        $report->setUser($user);

        $this->em->persist($report);
    }
}
