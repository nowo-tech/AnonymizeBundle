<?php

declare(strict_types=1);
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Nowo\AnonymizeBundle\AnonymizeBundle;
use Nowo\TwigInspectorBundle\NowoTwigInspectorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

return [
    FrameworkBundle::class         => ['all' => true],
    DoctrineBundle::class          => ['all' => true],
    DoctrineFixturesBundle::class  => ['dev' => true, 'test' => true],
    TwigBundle::class              => ['all' => true],
    DebugBundle::class             => ['dev' => true, 'test' => true],
    WebProfilerBundle::class       => ['dev' => true, 'test' => true],
    AnonymizeBundle::class         => ['dev' => true, 'test' => true],
    NowoTwigInspectorBundle::class => ['dev' => true, 'test' => true],
];
