<?php
namespace App\Controller;

use App\Model\Entity\Setting;
use App\Model\Entity\Role;
use App\Model\Table\SettingsTable;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;
use DateTime;
use Ramsey\Uuid\Uuid;


/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
	use MailerAwareTrait;

	/**
	 * @var \Authentication\Identity
	 */
	protected $identity;

    /**
     * Add method
     *
     * @return \Cake\Http\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
    	if ($this->identity && $this->identity->role != Role::ADMIN) {
    		$this->loadModel('Settings');
    		$setting = SettingsTable::getValue('..registerstatus');
			switch ($setting) {
				case Setting::REGISTER_STATUS_CLOSED || Setting::REGISTER_STATUS_API_ONLY:
					$this->Flash->error(__('Registrations are currently disabled.'));
					$this->redirect('/');
					break;
				case Setting::REGISTER_STATUS_INVITE && empty($this->request->getData('Request.invitecode')):
					$this->Flash->default(__('Registrations are currently by invitation only.'));
					$this->redirect(['controller' => 'Pages', 'action' => 'display', 'Home']);
					break;
				case Setting::REGISTER_STATUS_OPEN:
					break;
				default:
					throw new \InvalidArgumentException('Unknown registration status');
			}
		}

		$user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

				$this->getMailer('User')->send('welcome', [$user]);

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

	/**
	 *
	 *
	 * @param \Cake\Event\Event $event
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function beforeFilter(Event $event): ?Response
	{
		$this->Authentication->allowUnauthenticated(['add', 'forgotten', 'login', 'logout', ]);

		return parent::beforeFilter($event);
	}

	/**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

	/**
	 * Edit method
	 *
	 * @param string|null $id User id.
	 *
	 * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function edit($id = null): ?Response
	{
		$user = $this->Users->get($id,
			[
				'contain' => ['Releases']
			]
		);

		if ($this->request->is(['patch', 'post', 'put'])) {
			$user = $this->Users->patchEntity($user, $this->request->getData());
			if ($this->Users->save($user)) {
				$this->Flash->success(__('The user has been saved.'));

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('The user could not be saved. Please, try again.'));
		}
		$releases = $this->Users->Releases->find('list', ['limit' => 200]);
		$this->set(compact('user', 'releases'));
	}

	public function forgotten(): void
	{
		// If they're logged in forgotten password is not possible.
		if ($this->identity !== null && $this->identity->getIdentifier() !== null)
		{
			$this->redirect('/');
		}

		if ($this->request->is(['post'])) {
			$data = $this->request->getData('Post.email');

			$validator = new Validator();
			$validator->add('email', 'validFormat', [
					'rule' => 'email',
					'message' => 'Invalid e-mail format'
				]);
			$errors = $validator->errors(['email' => $data]);

			if (empty($errors)) {
				$query = $this->Users->findFromEmail($data);
			} else {
				$query = $this->Users->findFromUsername($data);
			}

			$query->select(['id', 'username', 'email'])
				->first();
			$user = $query->all();

			if ($user !== null) {
				$uid = Uuid::uuid4()->getHex();

				$url = Router::url([
						'controller' => 'Users',
						'action'     => 'reset',
						'id'         => $uid
					],
					true
				);

				$query = TableRegistry::getTableLocator()->get('PasswordResets')->query();
				$query->insert(['id', 'uid'])
					->values([$user->id, $uid])
					->execute();

				$this->set(['user' => $user, 'url' => $url]);

			$email = $this->getMailer('User');
			$email->send('forgotten', [$user]);
			}
		}
	}

	/**
	 * Index method
	 *
	 */
	public function index(): void
	{
		$users = $this->paginate($this->Users);

		$this->set(compact('users'));
	}

	public function initialize(): void
	{
		parent::initialize();

		//$this->Authorization->authorizeModel('add');
	}

	public function login()
	{
		$result = $this->Authentication->getResult();
		$this->set('user', $result);

		// regardless of POST or GET, redirect if user is logged in
		if ($result->isValid()) {
			$query = $this->Users->query();
			$query->update()
				->set(['lastlogin' => new DateTime('now')])
				->where(['id' => $this->identity->getIdentifier()])
				->execute();

			$redirect = $this->request->getQuery('redirect',
				['controller' => 'Pages', 'action' => 'display', 'home']);

			return $this->redirect($redirect);
		}

		// display error if user submitted and authentication failed
		if ($this->request->is(['post']) && !$result->isValid()) {
			$this->Flash->error('Invalid username or password');
		}
	}

	public function logout()
	{
		$this->Flash->success('You are now logged out.');

		$this->Authentication->logout();
		return $this->redirect($this->request->getQuery(
			'redirect',
			['controller' => 'Pages', 'action' => 'display', 'Home']
		));
	}

	public function reset(): void
	{
		// If they're logged in, reset is not possible.
		if ($this->identity !== null && $this->identity->getIdentifier() !== null) {
			$this->redirect('/');
		}
	}

	/**
	 * View method
	 *
	 * @param string|null $id User id.
	 *
	 * @return \Cake\Http\Response|void
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
	 */
	public function view($id = null)
	{
		if ($id === null || $this->identity->role != Role::ADMIN) {
			$id = $this->identity->getIdentifier();
		}

		$user = $this->Users->get($id,
			[
				/*
				'contain' => [
					'Releases',
					'ForumPosts',
					'Invitations',
					'ReleaseComments',
					'UserDownloads',
					'UserExcludedCategories',
					'UserMovies',
					'UserRequests',
					'UserSeries'
				]
				*/
			]);

		$this->set('user', $user);
	}
}
