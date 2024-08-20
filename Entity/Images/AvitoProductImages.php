<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Avito\Products\Entity\Images;

use BaksDev\Avito\Products\Entity\AvitoProduct;
use BaksDev\Files\Resources\Upload\UploadEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'avito_products_images')]
#[ORM\Index(columns: ['root'])]
class AvitoProductImages implements UploadEntityInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: AvitoProduct::class, inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'avito', referencedColumnName: 'id')]
    private AvitoProduct $avito;

    /** Название файла */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING)]
    private string $name;

    /** Расширение файла */
    #[Assert\NotBlank]
    #[Assert\Choice(['png', 'gif', 'jpg', 'jpeg', 'webp'])]
    #[ORM\Column(type: Types::STRING)]
    private string $ext;

    /** Размер файла */
    #[Assert\NotBlank]
    #[Assert\Range(max: 1048576)] // 1024 * 1024
    #[ORM\Column(type: Types::INTEGER)]
    private int $size = 0;

    /** Файл загружен на CDN */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $cdn = false;

    /** Заглавное фото */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $root = false;

    public function __construct(AvitoProduct $avito)
    {
        $this->avito = $avito;
    }

    public function __clone()
    {
        $this->avito = clone $this->avito;
    }

    public function __toString(): string
    {
        return (string)$this->avito;
    }

    public function updFile(string $name, string $ext, int $size): void
    {
        $this->name = $name;
        $this->ext = $ext;
        $this->size = $size;
        $this->cdn = false;
    }

    public function updCdn(?string $ext = null): void
    {
        if ($ext)
        {
            $this->ext = $ext;
        }

        $this->cdn = true;
    }

    public function getExt(): string
    {
        return $this->ext;
    }

    public function root(): void
    {
        $this->root = true;
    }

    public function getPathDir(): string
    {
        return $this->name;
    }
}
