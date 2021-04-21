<?php

namespace Drupal\metastore\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\metastore\RootedJsonDataWrapper;
use InvalidArgumentException;
use OpisErrorPresenter\Implementation\MessageFormatterFactory;
use OpisErrorPresenter\Implementation\PresentedValidationErrorFactory;
use OpisErrorPresenter\Implementation\ValidationErrorPresenter;
use RootedData\Exception\ValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class.
 */
class ProperJsonValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * @var \Drupal\metastore\RootedJsonDataWrapper
   */
  protected $rootedJsonDataFactory;

  /**
   * @var \OpisErrorPresenter\Implementation\ValidationErrorPresenter
   */
  protected $presenter;

  /**
   * ProperJsonValidator constructor.
   *
   * @param \Drupal\metastore\RootedJsonDataWrapper $rooted_json_data_factory
   *   dkan.metastore.rooted_json_data_wrapper service.
   */
  public function __construct(RootedJsonDataWrapper $rooted_json_data_factory) {
    $this->rootedJsonDataFactory = $rooted_json_data_factory;
    $this->presenter = new ValidationErrorPresenter(
      new PresentedValidationErrorFactory(
        new MessageFormatterFactory()
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dkan.metastore.rooted_json_data_wrapper')
    );
  }

  /**
   * Inherited.
   *
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $schema = 'dataset';
    if (is_object($items) && $type = $items->getParent()->getEntity()->get('field_data_type')->value) {
      $schema = $type;
    }
    foreach ($items as $item) {
      $errors = [];
      try {
        $this->rootedJsonDataFactory->createRootedJsonData($schema, $item->value);
      }
      catch (ValidationException $e) {
        $errors = $this->getValidationErrorsMessages($e->getResult()->getErrors());
      }
      catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
      }
      if (!empty($errors)) $this->addViolations($errors);
    }
  }

  /**
   * Presents errors.
   *
   * @param array $errors
   *   Validation errors array.
   *
   * @return array
   *   Presented errors array.
   */
  private function getValidationErrorsMessages(array $errors): array {
    $presented = $this->presenter->present(...$errors);
    return array_map(
      function($presented_error) {
        return $presented_error->message();
      },
      $presented
    );
  }

  /**
   * Add Violations.
   */
  private function addViolations($errors) {
    foreach ($errors as $error) {
      $this->context->addViolation($error);
    }
  }

}
