<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/post')]
class PostController extends AbstractController
{
    // Edit action: Edit a post
    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this post');
        }
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_topic_show', ['id' => $post->getTopic()->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    // Delete action: Delete a post
    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this post');
        }
        
        $topicId = $post->getTopic()->getId();
        
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_topic_show', ['id' => $topicId]);
    }
    #[Route('/post/{id}/like', name: 'app_post_like')]
    public function like(Post $post, EntityManagerInterface $entityManager): Response
    {
            // Get current user
        $user = $this->getUser();
            
            // Make sure user is logged in
            if (!$user) {
                throw $this->createAccessDeniedException('You must be logged in to like posts');
            }
            
            // Toggle like status
            if ($post->isLikedByUser($user)) {
                $post->removeLike($user);
            } else {
                $post->addLike($user);
            }
            
            $entityManager->flush();
            
            // Redirect back to the post or referring page
            return $this->redirect($this->generateUrl('app_topic_show', ['id' => $post->getTopic()->getId()]));
    }
    
}