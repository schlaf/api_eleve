<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Annotation
 */
class DateFrancaiseRequirement extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\NotNull(),
            new Assert\Type('string'),
            new Assert\Length(['min' => 1]),
            new Assert\Length(['max' => 64])
        ];
    }
}