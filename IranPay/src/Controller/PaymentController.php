<?php

namespace IranPay\Controller;


use IranPay\Core\Payment\PaymentService;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Payment\Exception\TokenExpiredException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PaymentController
 * @package IranPay\Controller
 * @author Hossein Haghparast
 */
class PaymentController extends StorefrontController
{

    /**
     * @var PaymentService
     */
    private $paymentService;


    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * this method has created in case the payment API does not support long JWT token
     *
     * @RouteScope(scopes={"storefront"})
     * @Route("/payment/f-i-t", defaults={"auth_required"=false}, name="payment.finalize.iran-transaction", methods={"GET", "POST"})
     *
     * @throws AsyncPaymentFinalizeException
     * @throws CustomerCanceledAsyncPaymentException
     * @throws InvalidTransactionException
     * @throws TokenExpiredException
     * @throws UnknownPaymentMethodException
     */
    public function finalizeTransaction(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $paymentToken = $request->get('_s_p_t');
        $paymentTokenStruct = $this->paymentService->finalizeTransaction(
            $paymentToken,
            $request,
            $salesChannelContext
        );

        if ($paymentTokenStruct->getException() !== null) {
            return new RedirectResponse($paymentTokenStruct->getErrorUrl());
        }

        if ($paymentTokenStruct->getFinishUrl()) {
            return new RedirectResponse($paymentTokenStruct->getFinishUrl());
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


}
