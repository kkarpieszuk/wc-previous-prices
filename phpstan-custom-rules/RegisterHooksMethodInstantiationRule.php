<?php

namespace PhpStanCustomRules;

use PHPStan\Reflection\ReflectionProvider;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;


/**
 * @implements Rule<Node\Expr\New_>
 */
class RegisterHooksMethodInstantiationRule implements Rule {

	/** @var ReflectionProvider */
	private $reflectionProvider;

	public function __construct( ReflectionProvider $reflectionProvider ) {

		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string {

		return Node\Expr\New_::class;
	}

	/**
	 * Classes which have method register_hooks()
	 * should be instantiated only in the PriorPrice\Hooks::plugins_loaded() method.
	 *
	 * @param Node\Expr\New_ $node
	 * @param Scope $scope
	 * @return array<string>
	 */
	public function processNode( Node $node, Scope $scope ): array {

		if ( ! $node->class instanceof Node\Name ) {
			return [];
		}

		$className = $node->class->toString();

		if ( ! $this->reflectionProvider->hasClass( $className ) ) {
			return [];
		}

		// This rule does not apply to PriorPrice\Hooks class.
		if ( $className === 'PriorPrice\Hooks' ) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass( $className );

		if ( ! $classReflection->hasMethod( 'register_hooks' ) ) {
			return [];
		}

		if ( $scope->isInClass()
			&& $scope->getFunctionName() === 'plugins_loaded'
			&& $scope->getClassReflection()->getName() === 'PriorPrice\Hooks'
		) {
			return [];
		}

		// If the class has method 'register_hooks' but is instantiated outside 'plugins_loaded'
		return [
			sprintf(
				'Class %s has method register_hooks and should only be instantiated in the PriorPrice\Hooks::plugins_loaded() method.',
				$className
			),
		];
	}
}