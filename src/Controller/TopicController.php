<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\Topic;
use App\Entity\Post;
use App\Form\TopicType;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/topic')]
class TopicController extends AbstractController
{
    // New action: Create a new topic
    #[Route('/new/{forum_id}', name: 'app_topic_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, int $forum_id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $forum = $entityManager->getRepository(Forum::class)->find($forum_id);
        
        if (!$forum) {
            throw $this->createNotFoundException('Forum not found');
        }
        
        $topic = new Topic();
        $topic->setForum($forum);
        $topic->setAuthor($this->getUser());
        
        // Explicitly set the createdAt field
        $topic->setCreatedAt(new \DateTimeImmutable());
        
        $post = new Post();
        $post->setAuthor($this->getUser());
        // Also set createdAt for the post
        $post->setCreatedAt(new \DateTimeImmutable());
        
        $form = $this->createForm(TopicType::class, $topic, [
            'post' => $post,
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Make sure createdAt is set again here in case form handling takes time
            $topic->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($topic);
            
            // Get the content from the form and set it on the post
            $post->setContent($form->get('content')->getData());
            $post->setTopic($topic);
            $post->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($post);
            
            $entityManager->flush();
    
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }
    
        return $this->render('topic/new.html.twig', [
            'topic' => $topic,
            'forum' => $forum,
            'form' => $form->createView(),
        ]);
    }

    

    #[Route('/{id}', name: 'app_topic_show', methods: ['GET', 'POST'])]
   
    public function show(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        $post = new Post(); 
        $post->setTopic($topic);
        
        // Only create the form for authenticated users
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $post->setAuthor($this->getUser());
            $form = $this->createForm(PostType::class, $post);
            
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($post);
                $entityManager->flush();
    
                return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
            }
            
            // Only pass the form to the template if it's created
            return $this->render('topic/show.html.twig', [
                'topic' => $topic,
                'form' => $form->createView(),
            ]);
        }
        
        // For non-authenticated users, don't pass a form
        return $this->render('topic/show.html.twig', [
            'topic' => $topic,
        ]);
    }

    // Edit action: Edit a topic
    #[Route('/{id}/edit', name: 'app_topic_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $topic->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this topic');
        }
        
        $form = $this->createForm(TopicType::class, $topic);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_topic_show', ['id' => $topic->getId()]);
        }

        return $this->render('topic/edit.html.twig', [
            'topic' => $topic,
            'form' => $form->createView(),
        ]);
    }

    // Delete action: Delete a topic
    #[Route('/{id}', name: 'app_topic_delete', methods: ['POST'])]
public function delete(Request $request, Topic $topic, EntityManagerInterface $entityManager): Response
{
    if (!$this->isGranted('ROLE_ADMIN') && $topic->getAuthor() !== $this->getUser()) {
        throw $this->createAccessDeniedException('You cannot delete this topic');
    }
    
    $forumId = $topic->getForum()->getId();
    
    if ($this->isCsrfTokenValid('delete'.$topic->getId(), $request->request->get('_token'))) {
        // This should remove the topic and all its posts due to cascade deletion
        $entityManager->remove($topic);
        $entityManager->flush();
        
        // Add a flash message to confirm deletion
        $this->addFlash('success', 'Topic has been deleted');
    }

    return $this->redirectToRoute('app_forum_show', ['id' => $forumId]);
}
}