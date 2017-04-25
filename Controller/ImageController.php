<?php

namespace SmartCore\Bundle\MediaBundle\Controller;

use Smart\CoreBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    /**
     * @param Request $request
     * @param         $collection
     * @param         $filter
     * @param         $slug
     *
     * @return Response
     */
    public function renderAction(Request $request, $collection, $filter, $slug)
    {
        $newImage = $this->get('smart_media')->generateTransformedFile($request->query->get('id', 0), $filter);

        $response = new Response($newImage);

        $filter_configuration = $this->get('liip_imagine.filter.configuration')->get($filter);

        $response->headers->set('Content-Type', 'image/'.$filter_configuration['format']);

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function uploadAction(Request $request)
    {
        $data = [
            'status' => 200,
            'success' => true,
        ];

        /**
         * @var string $key
         * @var UploadedFile $file
         */
        foreach ($request->files->all() as $key => $file) {
            // @todo указание коллекции
            $id = $this->get('smart_media')->getCollection(1)->upload($file);

            $data['data'][$key] = [
                'id' => $id,
                'original_name' => $file->getClientOriginalName(),
                'size'          => $file->getClientSize(),
                'mime_type'     => $file->getClientMimeType(),
                'thumbnail'     => $this->get('smart_media')->getFileUrl($id, '100x100'),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function removeAction(Request $request)
    {
        // @todo указание коллекции
        if ($this->get('smart_media')->getCollection(1)->remove($request->query->get('id'))) {
            $data = [
                'status' => 200,
                'success' => true,
            ];
        } else {
            $data = [
                'status' => 500,
                'success' => false,
            ];
        }

        return new JsonResponse($data);
    }
}
