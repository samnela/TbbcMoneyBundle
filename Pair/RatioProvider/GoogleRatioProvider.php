<?php
namespace Tbbc\MoneyBundle\Pair\RatioProvider;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Symfony\Component\DomCrawler\Crawler;
use Tbbc\MoneyBundle\MoneyException;
use Tbbc\MoneyBundle\Pair\RatioProviderInterface;

/**
 * GoogleRatioProvider
 * Fetches currencies ratios from google finance currency converter
 * @author Hugues Maignol <hugues.maignol@kitpages.fr>
 */
class GoogleRatioProvider implements RatioProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function fetchRatio($referenceCurrencyCode, $currencyCode)
    {
        $isoCurrencies = new ISOCurrencies();

        $baseCurrency = new Currency($referenceCurrencyCode);
        if (!$baseCurrency->isAvailableWithin($isoCurrencies)) {
            throw new MoneyException(
                sprintf('The currency code %s does not exists', $referenceCurrencyCode)
            );
        }

        $currency = new Currency($currencyCode);
        if (!$currency->isAvailableWithin($isoCurrencies)) {
            throw new MoneyException(
                sprintf('The currency code %s does not exists', $currencyCode)
            );
        }

        $baseUnits = 1000;
        $endpoint = $this->getEndpoint($baseUnits, $baseCurrency, $currency);
        $responseString = file_get_contents($endpoint);
        $convertedAmount = $this->getConvertedAmountFromResponse($responseString);
        $ratio = $convertedAmount / $baseUnits;

        return $ratio;
    }

    /**
     * @param string   $units
     * @param Currency $referenceCurrency
     * @param Currency $currency
     * @return string The endpoint to get Currency conversion
     */
    protected function getEndpoint($units, Currency $referenceCurrency, Currency $currency)
    {
        return sprintf(
            'https://finance.google.com/bctzjpnsun/converter?a=%s&from=%s&to=%s',
            $units,
            $referenceCurrency->getCode(),
            $currency->getCode()
        );
    }

    /**
     * @param string $response
     * @throws MoneyException
     * @return float The converted Amount
     */
    protected function getConvertedAmountFromResponse($response)
    {
        $crawler = new Crawler($response);
        $rawConvertedAmount = $crawler->filterXPath('//div[@id="currency_converter_result"]/span[@class="bld"]')->text();
        $floatConvertedAmount = (float) $rawConvertedAmount;

        if (! $rawConvertedAmount || $floatConvertedAmount <= 0) {
            throw new MoneyException("Cannot parse response from google finance converter API");
        }

        return $floatConvertedAmount;
    }
}
