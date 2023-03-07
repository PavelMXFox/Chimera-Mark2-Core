<?php namespace fox;

use fox\barcode\Datamatrix;
use fox\barcode\Barcode;
use fox\barcode\QRcode;
use fox\barcode\PDF417;

class foxBarcode implements externalCallable {
    static function APICall(request $request) {

      if (!$request->authOK) {
        throw new foxException("Unauthorized",401);
      }

      if ($request->method!=="POST") {
        throw new foxException("Method not allowed", 405);
      }
      $code=$request->requestBody->code;
      
      switch ($request->requestBody->type) {
        case "c128":
          $f=Barcode::factory()
          ->setCode($code)
          ->setType('C128')
          ->setScale(1)
          ->setHeight(100)
          ->setRotate(null)
          ->setColor(null);
          
          $im2=$f->getBarcodePngData(1);
          break;
        case "datamatrix":
          $f=Datamatrix::factory()
          ->setSize(null)
          ->setCode($code);
          $im2=$f->getDatamatrixPngData(1);
          break;

        case "qrcode":
          $f=QRcode::factory()
          ->setSize(200)
          ->setCode($code);  
          $im2=$f->getQRcodePngData(1);
          break;

        case "pdf417":
          $f=PDF417::factory()
          ->setSize(300)
          ->setCode($code);
      
          $im2=$f->getPDF417PngData(1);
          break;
        default:
          throw new foxException("Invalid type",400);
      }

      ob_clean();
      imagepng($im2);
      $stringdata = ob_get_contents(); // read from buffer
      ob_clean();
      return base64_encode($stringdata);
      
    }
}
