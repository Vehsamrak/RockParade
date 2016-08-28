<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Role;
use AppBundle\Controller\Infrastructure\RestController;
use AppBundle\Entity\Repository\RoleRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\Repository\UserRepository;
use AppBundle\Response\ApiError;
use AppBundle\Response\ApiResponse;
use AppBundle\Response\EmptyApiResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roles
 * @Route("role")
 * @author Vehsamrak
 */
class RoleController extends RestController
{

    /**
     * List all available user roles
     * @Route("s/{limit}/{offset}", name="roles_list")
     * @Method("GET")
     * @ApiDoc(
     *     section="Role",
     *     statusCodes={
     *         200="OK",
     *     }
     * )
     * @param int $limit Limit results. Default is 50
     * @param int $offset Starting serial number of result collection. Default is 0
     */
    public function listAction(int $limit = null, int $offset = null): Response
    {
        return $this->respond(
            $this->createCollectionResponse(
                $this->get('rockparade.role_repository'),
                $limit,
                $offset
            )
        );
    }

    /**
     * Assign roles to user
     * @Route("/assign", name="roles_assign")
     * @Method("POST")
     * @ApiDoc(
     *     section="Role",
     *     requirements={
     *         {
     *             "name"="login",
     *             "dataType"="string",
     *             "requirement"="\w",
     *             "description"="user login"
     *         },
     *         {
     *             "name"="roles",
     *             "dataType"="array",
     *             "requirement"="\w",
     *             "description"="applicable roles"
     *         }
     *     },
     *     statusCodes={
     *         200="Roles were assigned to user",
     *         400="Mandatory parameters are missed or not all provided roles are valid",
     *         404="User with provided login was not found",
     *     }
     * )
     * @param Request $request
     * @return Response
     */
    public function assignAction(Request $request): Response
    {
        $userLogin = filter_var($request->request->get('login'), FILTER_SANITIZE_STRING) ?: '';
        $roleNames = (array) $request->request->get('roles');

        if (empty($roleNames) || empty($userLogin)) {
            $response = new ApiError('Properties "login" and "roles" are mandatory.', Response::HTTP_BAD_REQUEST);
        } else {
            /** @var UserRepository $userRepository */
            $userRepository = $this->get('rockparade.user_repository');
            $user = $userRepository->findOneByLogin($userLogin);

            if ($user) {
                /** @var RoleRepository $roleRepository */
                $roleRepository = $this->get('rockparade.role_repository');
                $roles = $roleRepository->findByNames($roleNames);
                
                if (count($roleNames) === count($roles)) {
                    foreach ($roles as $role) {
                        $user->addRole($role);
                    }
                    
                    $roleRepository->flush();
                    $response = new EmptyApiResponse(Response::HTTP_OK);
                } else {
                    $response = new ApiError('Not all provided roles are valid.', Response::HTTP_BAD_REQUEST);
                }
            } else {
                $response = new ApiError('User with provided login was not found.', Response::HTTP_NOT_FOUND);
            }
        }

        return $this->respond($response);
    }
}
