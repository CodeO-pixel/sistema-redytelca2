<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

/**
 * Entidad Pagina
 */
final class Pagina extends BaseModel
{
    /** @var string */
    protected string $nombre_pagina = '';

    /** @var string */
    protected string $url_pagina = '';

    /** @var int */
    protected int $modulo_id = 0;

    /**
     * @return string
     */
    public function getNombrePagina(): string
    {
        return $this->nombre_pagina;
    }

    /**
     * @param string $nombre_pagina
     * @return self
     */
    public function setNombrePagina(string $nombre_pagina): self
    {
        $this->nombre_pagina = $nombre_pagina;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrlPagina(): string
    {
        return $this->url_pagina;
    }

    /**
     * @param string $url_pagina
     * @return self
     */
    public function setUrlPagina(string $url_pagina): self
    {
        $this->url_pagina = $url_pagina;
        return $this;
    }

    /**
     * @return int
     */
    public function getModuloId(): int
    {
        return $this->modulo_id;
    }

    /**
     * @param int $modulo_id
     * @return self
     */
    public function setModuloId(int $modulo_id): self
    {
        $this->modulo_id = $modulo_id;
        return $this;
    }
}