class Player:
    """
    Basic class to define a Player.
    Contains basic attributes of a player.
    """

    def __init__(
        self, name: str, age: int, role: str, experience: int, health: int, **kwargs
    ):
        """
        Constructor.
        Args:
            name (str): Name of the player.
            age (int): Age of the player.
            role (str): Role of the player (e.g., Batsman, Goalkeeper).
            experience (int): Experience of the player (0-100).
            health (int): Health of the player (0-100).
            **kwargs: Additional attributes like goals, wickets, etc.
        """
        self.name = name
        self.age = age
        self.role = role
        self.experience = experience
        self.health = health
        self.points = 0

        # Additional attributes for specific sports
        for key, value in kwargs.items():
            setattr(self, key, value)

    def calculate_influence(self):
        """
        Calculate the overall influence of a player in a game.
        Influence is calculated as experience * health / 100.
        Returns:
            float: The influence score.
        """
        return self.experience * self.health / 100
