<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class BlurFilter extends AbstractFilter
{
    const BLUR_KEY = 'blur';

    const BLUR_DEFAULT = 3;

    /**
     * @inheritdoc
     *
     * 0 = no blur, 3 = default, anything over 5 is extremely blurred.
     *
     * @author Martijn Frazer, idea based on http://stackoverflow.com/a/20264482
     */
    final public function apply(): FilterInterface
    {
        $blurFactor = $this->getSettingsValue(self::BLUR_KEY);
        $blurFactor = $this->clampValue($blurFactor);

        $originalWidth  = imagesx($this->image);
        $originalHeight = imagesy($this->image);
        $smallestWidth  = ceil($originalWidth * (0.5 ** $blurFactor));
        $smallestHeight = ceil($originalHeight * (0.5 ** $blurFactor));

        // For the first run, the previous image is the original input.
        $prevImage  = $this->image;
        $prevWidth  = $originalWidth;
        $prevHeight = $originalHeight;
        $nextImage  = null;
        $nextWidth  = 0;
        $nextHeight = 0;

        // Scale way down and gradually scale back up, blurring all the way.
        for ($i = 0; $i < $blurFactor; ++$i) {
            $nextWidth  = (int) $smallestWidth * (2 ** $i);
            $nextHeight = (int) $smallestHeight * (2 ** $i);

            // Resize previous image to next size.
            $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);

            imagecopyresized(
                $nextImage,
                $prevImage,
                0,
                0,
                0,
                0,
                $nextWidth,
                $nextHeight,
                $prevWidth,
                $prevHeight
            );

            imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);

            $prevImage = $nextImage;
            $prevWidth = $nextWidth;
            $prevHeight = $nextHeight;
        }

        // scale back to original size and blur one more time
        imagecopyresized(
            $this->image,
            $nextImage,
            0,
            0,
            0,
            0,
            $originalWidth,
            $originalHeight,
            $nextWidth,
            $nextHeight
        );

        imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        imagedestroy($prevImage);

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::BLUR_KEY => self::BLUR_DEFAULT,
        ];
    }

    /**
     * @param float $value
     *
     * @return int
     */
    private function clampValue(float $value): int
    {
        $clampedValue = (int) round($value);

        if ($clampedValue > 6) {
            $clampedValue = 6;
        }

        if ($clampedValue < 0) {
            $clampedValue = 0;
        }

        return $clampedValue;
    }
}
