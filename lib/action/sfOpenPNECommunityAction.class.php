<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * sfOpenPNECommunityAction
 *
 * @package    OpenPNE
 * @subpackage action
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class sfOpenPNECommunityAction extends sfActions
{
  public function preExecute()
  {
    $this->id = $this->getRequestParameter('id');

    $this->isCommunityMember = CommunityMemberPeer::isMember($this->getUser()->getMemberId(), $this->id);
    $this->isAdmin = CommunityMemberPeer::isAdmin($this->getUser()->getMemberId(), $this->id);
    $this->isEditCommunity = $this->isAdmin;
  }

 /**
  * Executes home action
  *
  * @param sfRequest $request A request object
  */
  public function executeHome($request)
  {
    $this->community = CommunityPeer::retrieveByPk($this->id);
    $this->forward404Unless($this->community, 'Undefined community.');
    $this->community_admin = CommunityMemberPeer::getCommunityAdmin($this->id);
    $this->community_admin = MemberPeer::retrieveByPk($this->community_admin->getMemberId());
  }

 /**
  * Executes edit action
  *
  * @param sfRequest $request A request object
  */
  public function executeEdit($request)
  {
    if ($this->id && !$this->isEditCommunity)
    {
      $this->forward('default', 'secure');
    }

    $this->community = CommunityPeer::retrieveByPk($this->id);
    $this->form = new CommunityForm($this->community);

    if ($request->isMethod('post'))
    {
      $params = $request->getParameter('community');
      $params['id'] = $this->id;
      $this->form->bind($params, $request->getFiles('community'));
      if ($this->form->isValid())
      {
        $community = $this->form->save();

        $this->redirect('community/home?id='.$community->getId());
      }
    }
  }

 /**
  * Executes joinlist action
  *
  * @param sfRequest $request A request object
  */
  public function executeJoinlist($request)
  {
    $memberId = $request->getParameter('member_id', $this->getUser()->getMemberId());

    $this->member = MemberPeer::retrieveByPK($memberId);
    $this->forward404Unless($this->member);

    $this->pager = CommunityPeer::getJoinCommunityListPager($memberId, $request->getParameter('page', 1));

    if (!$this->pager->getNbResults())
    {
      return sfView::ERROR;
    }

    return sfView::SUCCESS;
  }

 /**
  * Executes memberList action
  *
  * @param sfRequest $request A request object
  */
  public function executeMemberList($request)
  {
    $this->community = CommunityPeer::retrieveByPk($this->id);
    $this->pager = CommunityPeer::getCommunityMemberListPager($this->id, $request->getParameter('page', 1));

    if (!$this->pager->getNbResults()) {
      return sfView::ERROR;
    }

    return sfView::SUCCESS;
  }

 /**
  * Executes join action
  *
  * @param sfRequest $request A request object
  */
  public function executeJoin($request)
  {
    if ($this->isCommunityMember) {
      return sfView::ERROR;
    }

    CommunityMemberPeer::join($this->getUser()->getMemberId(), $this->id);
    $this->redirect('community/home?id=' . $this->id);
  }

 /**
  * Executes quit action
  *
  * @param sfRequest $request A request object
  */
  public function executeQuit($request)
  {
    if (!$this->isCommunityMember || $this->isAdmin)
    {
      return sfView::ERROR;
    }

    CommunityMemberPeer::quit($this->getUser()->getMemberId(), $this->id);
    $this->redirect('community/home?id=' . $this->id);
  }

 /**
  * Executes memberManage action
  *
  * @param sfRequest $request A request object
  */
  public function executeMemberManage($request)
  {
    $this->redirectUnless($this->isAdmin, '@error');

    $this->community = CommunityPeer::retrieveByPk($this->id);
    $this->pager = CommunityPeer::getCommunityMemberListPager($this->id, $request->getParameter('page', 1));

    if (!$this->pager->getNbResults())
    {
      return sfView::ERROR;
    }

    return sfView::SUCCESS;
  }

 /**
  * Executes dropMember action
  *
  * @param sfRequest $request A request object
  */
  public function executeDropMember($request)
  {
    $this->redirectUnless($this->isAdmin, '@error');
    $member = MemberPeer::retrieveByPk($request->getParameter('member_id'));
    $this->forward404Unless($member);

    $isCommunityMember = CommunityMemberPeer::isMember($member->getId(), $this->id);
    $this->redirectUnless($this->isAdmin, '@error');
    $isAdmin = CommunityMemberPeer::isAdmin($member->getId(), $this->id);
    $this->redirectIf($isAdmin, '@error');

    CommunityMemberPeer::quit($member->getId(), $this->id);
    $this->redirect('community/memberManage?id='.$this->id);
  }

}
