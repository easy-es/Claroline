<?php

namespace Claroline\CoreBundle\Repository;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Workspace\WorkspaceTag;
use Doctrine\ORM\EntityRepository;

class WorkspaceTagHierarchyRepository extends EntityRepository
{
    /**
     * Returns all relations where parentId is in the param array
     */
    public function findAdminHierarchiesByParents(array $parents)
    {
        if (count($parents) === 0) {
            throw new \InvalidArgumentException("Array argument cannot be empty");
        }

        $index = 0;
        $eol = PHP_EOL;
        $parentsTest = "(";

        foreach ($parents as $parent) {
            $parentsTest .= $index > 0 ? "    OR " : "    ";
            $parentsTest .= "p.id = {$parent}{$eol}";
            $index++;
        }
        $parentsTest .= "){$eol}";

        $dql = "
            SELECT h
            FROM Claroline\CoreBundle\Entity\Workspace\WorkspaceTagHierarchy h
            JOIN h.parent p
            WHERE h.user IS NULL
            AND {$parentsTest}
        ";
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns all relations where parentId is in the param array
     */
    public function findAllAdminHierarchiesByParents(array $parents)
    {
        if (count($parents) === 0) {
            throw new \InvalidArgumentException("Array argument cannot be empty");
        }

        $index = 0;
        $eol = PHP_EOL;
        $parentsTest = "(";

        foreach ($parents as $parent) {
            $parentsTest .= $index > 0 ? "    OR " : "    ";
            $parentsTest .= "t.id = {$parent}{$eol}";
            $index++;
        }
        $parentsTest .= "){$eol}";

        $dql = "
            SELECT h
            FROM Claroline\CoreBundle\Entity\Workspace\WorkspaceTagHierarchy h
            JOIN h.parent t
            WHERE h.user IS NULL
            AND {$parentsTest}
        ";
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Find all admin hierarchies (with level > 0) where
     * parents and children ids are in the given arrays
     */
    public function findAdminHierarchiesByParentsAndChildren(array $parents, array $children)
    {
        if (count($parents) === 0 || count($children) === 0) {
            throw new \InvalidArgumentException("Array argument cannot be empty");
        }

        $parentIndex = 0;
        $eol = PHP_EOL;
        $parentsTest = "(";

        foreach ($parents as $parent) {
            $parentsTest .= $parentIndex > 0 ? "    OR " : "    ";
            $parentsTest .= "p.id = {$parent}{$eol}";
            $parentIndex++;
        }
        $parentsTest .= "){$eol}";

        $childrenIndex = 0;
        $childrenTest = "(";

        foreach ($children as $child) {
            $childrenTest .= $childrenIndex > 0 ? "    OR " : "    ";
            $childrenTest .= "t.id = {$child}{$eol}";
            $childrenIndex++;
        }
        $childrenTest .= "){$eol}";

        $dql = "
            SELECT h
            FROM Claroline\CoreBundle\Entity\Workspace\WorkspaceTagHierarchy h
            JOIN h.parent p
            JOIN h.tag t
            WHERE h.user IS NULL
            AND h.level > 0
            AND {$parentsTest}
            AND {$childrenTest}
        ";
        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
}