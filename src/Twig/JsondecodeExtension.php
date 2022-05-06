<?php
namespace App\Twig;
use App\Utils\Markdown;
use Symfony\Component\Intl\Intl;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class JsondecodeExtension extends AbstractExtension
{
	public function getFilters(): array
    {
        return [
            new TwigFilter('json_obj', [$this, 'jsonDecodeObj']),
			new TwigFilter('json_arr', [$this, 'jsonDecodeArr']),
			new TwigFilter('url_encode', [$this, 'urlEncode']),
        ];
    }
	public function jsonDecodeObj(string $content): object
    {
        return json_decode($content);
    }
	public function jsonDecodeArr(string $content): array
    {
        return json_decode($content, true);
    }
	public function url_encode(string $content): string
    {
        return urlencode($content);
    }
	public function getName()
    {
        return 'jsondecode_extension';
    }
}
?>