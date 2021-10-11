<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Helper;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Class SubjectReader - Reading data.
 */
class SubjectReader {

  /** @var Checkout Session */
  private $checkoutSession;

  /**
   * SubjectReader constructor.
   * @param Session $checkoutSession
   */
  public function __construct(Session $checkoutSession) {
    $this->checkoutSession = $checkoutSession;
  }

  /**
   * Reads payment from subject.
   * @param array $subject
   * @return PaymentDataObjectInterface
   */
  public function readPayment(array $subject): PaymentDataObjectInterface {
    return Helper\SubjectReader::readPayment($subject);
  }

  /**
   * Reads store's ID, otherwise returns null.
   * @param array $subject
   * @return int|null
   */
  public function readStoreId(array $subject): int {
    $storeId = $subject['store_id'] ?? null;

    if (empty($storeId)) {
      try {
        $storeId = (int) $this->readPayment($subject)
          ->getOrder()
          ->getStoreId();
      } catch (\InvalidArgumentException $e) {
        // No store id is current set
      }
    }

    return $storeId ? (int) $storeId : null;
  }

  /**
   * Reads amount from subject.
   * @param array $subject
   * @return string
   */
  public function readAmount(array $subject): string {
    return (string) Helper\SubjectReader::readAmount($subject);
  }

  /**
   * Reads response from subject.
   * @param array $subject
   * @return array
   */
  public function readResponse(array $subject): array {
    return Helper\SubjectReader::readResponse($subject);
  }

  /**
   * @return Quote
   */
  public function getQuote() {
    return $this->checkoutSession->getQuote();
  }

  /**
   * @return Order
   */
  public function getOrder() {
    return $this->checkoutSession->getLastRealOrder();
  }
}
