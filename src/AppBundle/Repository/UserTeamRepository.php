<?php
namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class UserTeamRepository extends EntityRepository
{
    public function addPokemonsToTeam(array $pokemons, int $pokemonsAlreadyInTeam, int $userId): void
    {
        $kwer = '';

        $count = count($pokemons);
        for ($i = 0; $i < $count; $i++, $pokemonsAlreadyInTeam++) {
            $kwer .= ' u.pokemon'.($pokemonsAlreadyInTeam+1). ' = '. $pokemons[$i] . ', ';
        }
        $kwer = rtrim($kwer, ', ');

        $query = $this->_em->createQuery('UPDATE AppBundle:UserTeam u SET ' . $kwer . ' WHERE u.id = '.$userId);
        $query->execute();
    }

    public function deletePokemonFromTeam(int $i, int $userId): void
    {
        $kwer = '';

        for ($j = $i+1; $j < 6; $j++) {
            $kwer .= 'u.pokemon'.$j.' = u.pokemon'.($j+1).',';
        }

        $kwer .= ' u.pokemon6 = 0';

        $query = $this->_em->createQuery('UPDATE AppBundle:UserTeam u SET ' . $kwer . ' WHERE u.id = '.$userId);
        $query->execute();
    }

    public function changeOrder(array $order, int $userId): void
    {
        $keys = array_keys($order);

        $kwer = 'u.pokemon' . $keys[0] . ' = ' . $order[$keys[0]] .', u.pokemon' . $keys[1] . ' = ' . $order[$keys[1]];


        $query = $this->_em->createQuery('UPDATE AppBundle:UserTeam u SET ' . $kwer . ' WHERE u.id = '.$userId);
        $query->execute();
    }
}
