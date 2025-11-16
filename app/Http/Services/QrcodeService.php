<?php

namespace App\Http\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrcodeService
{
    /**
     * Génère un QR code pour un compte bancaire
     *
     * @param string $numeroCompte
     * @return string QR code image in PNG format
     */
    public function generateForAccount(string $numeroCompte): string
    {
        // Créer les données du QR code (par exemple, numéro de compte)
        $data = "BANQUE:COMPTE:{$numeroCompte}";

        // Générer le QR code en base64
        return QrCode::format('png')
            ->size(200)
            ->generate($data);
    }

    /**
     * Génère un QR code et le retourne en base64
     *
     * @param string $numeroCompte
     * @return string
     */
    public function generateBase64(string $numeroCompte): string
    {
        $qrCode = $this->generateForAccount($numeroCompte);

        // Encoder en base64
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }
}